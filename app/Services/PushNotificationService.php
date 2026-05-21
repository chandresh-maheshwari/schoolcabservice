<?php

namespace App\Services;

use App\Models\Child;
use App\Models\ChildSubscription;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PushNotificationService
{
    public const SETTINGS_TABLE = 'push_notification_settings';
    public const EVENT_LOGS_TABLE = 'push_notification_event_logs';
    private const PUSH_CHANNEL_ID = 'scb_push_channel_v2';
    private static ?string $cachedAccessToken = null;
    private static ?Carbon $cachedAccessTokenExpiresAt = null;

    public function defaults(): array
    {
        return collect($this->eventDefinitions())
            ->mapWithKeys(fn (array $definition, string $eventKey) => [
                $eventKey => Arr::only($definition, ['enabled', 'title_template', 'message_template', 'label']),
            ])
            ->all();
    }

    public function eventDefinitions(): array
    {
        return [
            'vehicle_near_pickup' => [
                'label' => 'Vehicle near pickup',
                'enabled' => true,
                'title_template' => 'Vehicle near pickup stop',
                'message_template' => "{{childName}}'s vehicle is near {{stopLabel}}.",
            ],
            'vehicle_arrived_pickup' => [
                'label' => 'Vehicle arrived pickup',
                'enabled' => true,
                'title_template' => 'Vehicle arrived at pickup stop',
                'message_template' => "{{childName}}'s vehicle has arrived at {{stopLabel}}.",
            ],
            'child_picked_up' => [
                'label' => 'Child picked up',
                'enabled' => true,
                'title_template' => 'Child picked up',
                'message_template' => '{{childName}} has been picked up successfully.',
            ],
            'vehicle_near_school' => [
                'label' => 'Vehicle near school',
                'enabled' => true,
                'title_template' => 'Vehicle near school',
                'message_template' => "{{childName}}'s vehicle is almost at school.",
            ],
            'vehicle_arrived_school' => [
                'label' => 'Vehicle arrived school',
                'enabled' => true,
                'title_template' => 'Vehicle arrived at school',
                'message_template' => "{{childName}}'s vehicle has reached school.",
            ],
            'child_arrived_school' => [
                'label' => 'Child arrived school',
                'enabled' => true,
                'title_template' => 'Child arrived at school',
                'message_template' => '{{childName}} has arrived at school safely.',
            ],
            'vehicle_near_dropoff' => [
                'label' => 'Vehicle near dropoff',
                'enabled' => true,
                'title_template' => 'Vehicle near drop-off stop',
                'message_template' => "{{childName}}'s vehicle is near {{stopLabel}}.",
            ],
            'vehicle_arrived_dropoff' => [
                'label' => 'Vehicle arrived dropoff',
                'enabled' => true,
                'title_template' => 'Vehicle arrived at drop-off stop',
                'message_template' => "{{childName}}'s vehicle has arrived at {{stopLabel}}.",
            ],
            'child_dropped_home' => [
                'label' => 'Child dropped home',
                'enabled' => true,
                'title_template' => 'Child dropped successfully',
                'message_template' => '{{childName}} has been dropped successfully.',
            ],
            'trip_started' => [
                'label' => 'Trip started',
                'enabled' => false,
                'title_template' => 'Trip started',
                'message_template' => 'The driver has started the {{tripType}} trip.',
            ],
            'driver_emergency_alert' => [
                'label' => 'Driver emergency alert',
                'enabled' => true,
                'title_template' => 'Driver emergency alert',
                'message_template' => '{{driverName}} reported {{emergencyType}} on {{routeLabel}}{{detailSuffix}}',
            ],
            'subscription_created' => [
                'label' => 'Subscription created',
                'enabled' => true,
                'title_template' => 'Subscription activated',
                'message_template' => '{{childName}} subscription is active till {{expiresAt}} for {{serviceType}} service.',
            ],
            'subscription_expiring_soon' => [
                'label' => 'Subscription expiring soon',
                'enabled' => true,
                'title_template' => 'Subscription expiring soon',
                'message_template' => '{{childName}} subscription will expire on {{expiresAt}}. Only {{daysLeft}} day(s) left.',
            ],
            'subscription_expired' => [
                'label' => 'Subscription expired',
                'enabled' => true,
                'title_template' => 'Subscription expired',
                'message_template' => '{{childName}} subscription expired on {{expiresAt}}. Please renew to continue service.',
            ],
            'manual_admin_push' => [
                'label' => 'Manual admin push',
                'enabled' => true,
                'title_template' => '{{title}}',
                'message_template' => '{{message}}',
            ],
        ];
    }

    public function settings(): array
    {
        $defaults = $this->defaults();
        if (! Schema::hasTable(self::SETTINGS_TABLE)) {
            return $defaults;
        }

        $settingsColumns = Schema::getColumnListing(self::SETTINGS_TABLE);
        foreach ($defaults as $eventKey => $config) {
            $exists = DB::table(self::SETTINGS_TABLE)
                ->where('event_key', $eventKey)
                ->exists();

            if (! $exists) {
                $record = [
                    'event_key' => $eventKey,
                    'enabled' => $config['enabled'] ? 1 : 0,
                    'title_template' => $config['title_template'],
                    'message_template' => $config['message_template'],
                    'metadata' => json_encode(['source' => 'default', 'label' => $config['label'] ?? null]),
                ];
                if (in_array('createdAt', $settingsColumns, true)) {
                    $record['createdAt'] = now();
                }
                if (in_array('updated_at', $settingsColumns, true)) {
                    $record['updated_at'] = now();
                } elseif (in_array('updatedAt', $settingsColumns, true)) {
                    $record['updatedAt'] = now();
                }

                DB::table(self::SETTINGS_TABLE)->insert($record);
            }
        }

        $rows = DB::table(self::SETTINGS_TABLE)->get();
        foreach ($rows as $row) {
            $defaults[$row->event_key] = [
                'enabled' => (bool) $row->enabled,
                'title_template' => $row->title_template,
                'message_template' => $row->message_template,
                'label' => data_get(json_decode((string) $row->metadata, true) ?: [], 'label', $defaults[$row->event_key]['label'] ?? $row->event_key),
            ];
        }

        return $defaults;
    }

    public function saveSettings(array $settings): void
    {
        if (! Schema::hasTable(self::SETTINGS_TABLE)) {
            return;
        }

        $settingsColumns = Schema::getColumnListing(self::SETTINGS_TABLE);
        foreach ($settings as $eventKey => $config) {
            $payload = [
                'enabled' => ! empty($config['enabled']) ? 1 : 0,
                'title_template' => (string) ($config['title_template'] ?? ''),
                'message_template' => (string) ($config['message_template'] ?? ''),
                'metadata' => json_encode(['label' => $config['label'] ?? ($this->defaults()[$eventKey]['label'] ?? $eventKey)]),
            ];
            if (in_array('updated_at', $settingsColumns, true)) {
                $payload['updated_at'] = now();
            } elseif (in_array('updatedAt', $settingsColumns, true)) {
                $payload['updatedAt'] = now();
            }

            $updated = DB::table(self::SETTINGS_TABLE)
                ->where('event_key', $eventKey)
                ->update($payload);

            if (! $updated) {
                DB::table(self::SETTINGS_TABLE)->insert(array_merge(
                    ['event_key' => $eventKey],
                    $payload,
                    in_array('createdAt', $settingsColumns, true) ? ['createdAt' => now()] : []
                ));
            }
        }
    }

    public function sendEventToUsers(string $eventKey, array $userIds, array $templateData = [], array $data = []): array
    {
        $settings = $this->settings();
        $event = $settings[$eventKey] ?? null;

        if (! $event || empty($event['enabled'])) {
            return [
                'targeted_users' => count(array_unique(array_map('intval', $userIds))),
                'stored' => 0,
                'matched_tokens' => 0,
                'sent' => 0,
                'skipped' => true,
            ];
        }

        $title = $this->renderTemplate((string) ($event['title_template'] ?? ''), $templateData);
        $message = $this->renderTemplate((string) ($event['message_template'] ?? ''), $templateData);

        return $this->sendToUsers(
            $userIds,
            $title,
            $message,
            $eventKey,
            array_merge($data, [
                'eventKey' => $eventKey,
                'templateData' => $this->stringifyData($templateData),
            ])
        );
    }

    public function sendSubscriptionCreatedNotification(ChildSubscription $subscription): array
    {
        $context = $this->subscriptionContext($subscription);
        if (empty($context['user_ids'])) {
            return [
                'targeted_users' => 0,
                'stored' => 0,
                'matched_tokens' => 0,
                'sent' => 0,
                'skipped' => true,
            ];
        }

        return $this->sendEventToUsers(
            'subscription_created',
            $context['user_ids'],
            $context['template_data'],
            $context['payload']
        );
    }

    public function sendSubscriptionExpiryNotifications(?Carbon $today = null): array
    {
        $today = ($today ?: now())->copy()->startOfDay();
        $processed = 0;
        $sent = 0;

        ChildSubscription::query()
            ->with(['child.parent', 'child.school'])
            ->where('is_current', 1)
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use ($today, &$processed, &$sent) {
                foreach ($subscriptions as $subscription) {
                    $processed++;

                    $expiresAt = Carbon::parse($subscription->expires_at)->startOfDay();
                    $daysLeft = $today->diffInDays($expiresAt, false);

                    if ($daysLeft < 0) {
                        $result = $this->sendSubscriptionLifecycleEventOnce($subscription, 'subscription_expired', $expiresAt->toDateString(), [
                            'daysLeft' => 0,
                        ]);
                    } elseif (in_array($daysLeft, [0, 1, 3], true)) {
                        $result = $this->sendSubscriptionLifecycleEventOnce($subscription, 'subscription_expiring_soon', $expiresAt->toDateString(), [
                            'daysLeft' => $daysLeft,
                        ]);
                    } else {
                        continue;
                    }

                    $sent += (int) ($result['sent'] ?? 0);
                }
            });

        return [
            'processed' => $processed,
            'sent' => $sent,
        ];
    }

    public function sendToUsers(array $userIds, string $title, string $message, string $type = 'manual', array $data = []): array
    {
        $userIds = collect($userIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($userIds) || trim($title) === '' || trim($message) === '') {
            return [
                'targeted_users' => count($userIds),
                'stored' => 0,
                'matched_tokens' => 0,
                'sent' => 0,
            ];
        }

        $notificationTableColumns = Schema::hasTable('mobile_notifications')
            ? Schema::getColumnListing('mobile_notifications')
            : [];

        $stored = 0;
        if (! empty($notificationTableColumns)) {
            foreach ($userIds as $userId) {
                $payload = [
                    'user_id' => $userId,
                    'title' => $title,
                    'type' => $type,
                ];

                if (in_array('message', $notificationTableColumns, true)) {
                    $payload['message'] = $message;
                }
                if (in_array('body', $notificationTableColumns, true)) {
                    $payload['body'] = $message;
                }
                if (in_array('data', $notificationTableColumns, true)) {
                    $payload['data'] = json_encode($data);
                }
                if (in_array('payload', $notificationTableColumns, true)) {
                    $payload['payload'] = json_encode($data);
                }
                if (in_array('is_read', $notificationTableColumns, true)) {
                    $payload['is_read'] = 0;
                }
                if (in_array('sent_at', $notificationTableColumns, true)) {
                    $payload['sent_at'] = now();
                }
                if (in_array('createdAt', $notificationTableColumns, true)) {
                    $payload['createdAt'] = now();
                }
                if (in_array('created_at', $notificationTableColumns, true)) {
                    $payload['created_at'] = now();
                }
                if (in_array('updated_at', $notificationTableColumns, true)) {
                    $payload['updated_at'] = now();
                } elseif (in_array('updatedAt', $notificationTableColumns, true)) {
                    $payload['updatedAt'] = now();
                }

                DB::table('mobile_notifications')->insert($payload);
                $stored++;
            }
        }

        $tokens = $this->deviceTokensForUsers($userIds);
        $sent = $this->sendFcm($tokens, $title, $message, $data, $userIds);

        return [
            'targeted_users' => count($userIds),
            'stored' => $stored,
            'matched_tokens' => count($tokens),
            'sent' => $sent,
        ];
    }

    private function sendSubscriptionLifecycleEventOnce(
        ChildSubscription $subscription,
        string $eventKey,
        string $uniqueKey,
        array $extraTemplateData = []
    ): array {
        $context = $this->subscriptionContext($subscription, $extraTemplateData);
        if (empty($context['user_ids'])) {
            return [
                'targeted_users' => 0,
                'stored' => 0,
                'matched_tokens' => 0,
                'sent' => 0,
                'skipped' => true,
            ];
        }

        if ($this->wasEventSent($eventKey, (int) $subscription->id, $uniqueKey)) {
            return [
                'targeted_users' => count($context['user_ids']),
                'stored' => 0,
                'matched_tokens' => 0,
                'sent' => 0,
                'duplicate' => true,
            ];
        }

        $result = $this->sendEventToUsers(
            $eventKey,
            $context['user_ids'],
            $context['template_data'],
            $context['payload']
        );

        if (((int) ($result['stored'] ?? 0)) > 0 || ((int) ($result['sent'] ?? 0)) > 0) {
            $this->markEventSent($eventKey, (int) $subscription->id, $uniqueKey, $context['payload']);
        }

        return $result;
    }

    private function subscriptionContext(ChildSubscription $subscription, array $extraTemplateData = []): array
    {
        $subscription->loadMissing(['child.parent', 'child.school']);
        /** @var Child|null $child */
        $child = $subscription->child;
        $parent = $child?->parent;

        $userIds = collect([
            (int) ($parent->login_user_id ?? 0),
            (int) ($parent->user_id ?? 0),
        ])->filter(fn ($id) => $id > 0)->unique()->values()->all();

        $expiresAt = $subscription->expires_at ? Carbon::parse($subscription->expires_at) : null;
        $startsAt = $subscription->starts_at ? Carbon::parse($subscription->starts_at) : null;

        $templateData = array_merge([
            'childName' => (string) ($child->child_name ?? 'Child'),
            'serviceType' => ucfirst(str_replace('_', ' ', (string) $subscription->service_type)),
            'packageType' => ucfirst(str_replace('_', ' ', (string) $subscription->package_type)),
            'schoolName' => (string) ($child?->school?->school_name ?? ''),
            'startsAt' => $startsAt ? $startsAt->format('d M Y, h:i A') : '',
            'expiresAt' => $expiresAt ? $expiresAt->format('d M Y, h:i A') : '',
            'subscriptionStatus' => (string) $subscription->status,
        ], $extraTemplateData);

        return [
            'user_ids' => $userIds,
            'template_data' => $templateData,
            'payload' => [
                'subscriptionId' => (int) $subscription->id,
                'childId' => (int) ($subscription->child_id ?? 0),
                'serviceType' => (string) $subscription->service_type,
                'packageType' => (string) $subscription->package_type,
                'status' => (string) $subscription->status,
                'expiresAt' => $expiresAt?->toIso8601String(),
            ],
        ];
    }

    private function renderTemplate(string $template, array $data): string
    {
        $rendered = preg_replace_callback('/{{\s*([a-zA-Z0-9_]+)\s*}}/', function (array $matches) use ($data) {
            $value = $data[$matches[1]] ?? '';
            return is_scalar($value) || $value === null ? (string) $value : '';
        }, $template);

        return trim((string) $rendered);
    }

    private function wasEventSent(string $eventKey, int $entityId, string $uniqueKey): bool
    {
        if (! Schema::hasTable(self::EVENT_LOGS_TABLE)) {
            return false;
        }

        return DB::table(self::EVENT_LOGS_TABLE)
            ->where('event_key', $eventKey)
            ->where('entity_type', 'child_subscription')
            ->where('entity_id', $entityId)
            ->where('unique_key', $uniqueKey)
            ->exists();
    }

    private function markEventSent(string $eventKey, int $entityId, string $uniqueKey, array $payload = []): void
    {
        if (! Schema::hasTable(self::EVENT_LOGS_TABLE)) {
            return;
        }

        $eventColumns = Schema::getColumnListing(self::EVENT_LOGS_TABLE);
        $updates = [
            'payload' => json_encode($payload),
        ];
        if (in_array('createdAt', $eventColumns, true)) {
            $updates['createdAt'] = now();
        }
        if (in_array('updated_at', $eventColumns, true)) {
            $updates['updated_at'] = now();
        } elseif (in_array('updatedAt', $eventColumns, true)) {
            $updates['updatedAt'] = now();
        }

        DB::table(self::EVENT_LOGS_TABLE)->updateOrInsert(
            [
                'event_key' => $eventKey,
                'entity_type' => 'child_subscription',
                'entity_id' => $entityId,
                'unique_key' => $uniqueKey,
            ],
            $updates
        );
    }

    private function deviceTokensForUsers(array $userIds): array
    {
        if (! Schema::hasTable('device_tokens')) {
            return [];
        }

        $columns = Schema::getColumnListing('device_tokens');
        $tokenColumn = in_array('token', $columns, true) ? 'token' : (in_array('device_token', $columns, true) ? 'device_token' : null);
        if ($tokenColumn === null) {
            return [];
        }

        $emails = [];
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'email')) {
            $emails = DB::table('users')
                ->whereIn('id', $userIds)
                ->pluck('email')
                ->map(fn ($email) => mb_strtolower(trim((string) $email)))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return DB::table('device_tokens')
            ->where(function ($query) use ($userIds, $emails) {
                $query->whereIn('user_id', $userIds);
                if (! empty($emails) && Schema::hasColumn('device_tokens', 'email')) {
                    $query->orWhereIn(DB::raw('LOWER(TRIM(email))'), $emails);
                }
            })
            ->orderByDesc(in_array('updated_at', $columns, true) ? 'updated_at' : 'updatedAt')
            ->pluck($tokenColumn)
            ->map(fn ($token) => trim((string) $token))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function sendFcm(array $tokens, string $title, string $message, array $data = [], array $userIds = []): int
    {
        $tokens = collect($tokens)->map(fn ($token) => trim((string) $token))->filter()->unique()->values()->all();
        if (empty($tokens)) {
            Log::info('Push skipped in Laravel panel because no device tokens matched users', [
                'user_ids' => $userIds,
            ]);
            return 0;
        }

        $stringData = $this->stringifyData(array_merge($data, [
            'title' => $title,
            'message' => $message,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ]));

        $serviceAccount = $this->loadServiceAccount();
        $accessToken = $serviceAccount ? $this->firebaseAccessToken($serviceAccount) : null;
        if ($serviceAccount && $accessToken) {
            $sent = 0;
            foreach ($tokens as $token) {
                try {
                    $response = Http::timeout(20)
                        ->withToken($accessToken)
                        ->post("https://fcm.googleapis.com/v1/projects/{$serviceAccount['project_id']}/messages:send", [
                            'message' => [
                                'token' => $token,
                                'notification' => [
                                    'title' => $title,
                                    'body' => $message,
                                ],
                                'data' => $stringData,
                                'android' => [
                                    'priority' => 'high',
                                    'notification' => [
                                        'channel_id' => self::PUSH_CHANNEL_ID,
                                        'sound' => 'default',
                                        'visibility' => 'PUBLIC',
                                        'notification_priority' => 'PRIORITY_MAX',
                                    ],
                                ],
                                'apns' => [
                                    'headers' => [
                                        'apns-priority' => '10',
                                    ],
                                    'payload' => [
                                        'aps' => [
                                            'alert' => [
                                                'title' => $title,
                                                'body' => $message,
                                            ],
                                            'sound' => 'default',
                                            'badge' => 1,
                                        ],
                                    ],
                                ],
                            ],
                        ]);

                    if ($response->successful()) {
                        $sent++;
                        continue;
                    }

                    $this->logPushFailure('FCM v1 send failed from Laravel panel', $response->status(), $response->json(), $token);
                    $this->deleteInvalidTokenIfNeeded($token, $response->json());
                } catch (\Throwable $exception) {
                    Log::warning('FCM v1 send failed from Laravel panel', [
                        'message' => $exception->getMessage(),
                        'token_suffix' => $this->tokenSuffix($token),
                    ]);
                }
            }

            return $sent;
        }

        $serverKey = trim((string) env('FCM_SERVER_KEY', env('FIREBASE_SERVER_KEY', '')));

        if ($serverKey === '') {
            Log::warning('Push skipped in Laravel panel because Firebase credentials are missing', [
                'token_count' => count($tokens),
            ]);
            return 0;
        }

        $sent = 0;
        foreach (array_chunk($tokens, 500) as $chunk) {
            try {
                $response = Http::timeout(20)
                    ->withHeaders([
                        'Authorization' => 'key=' . $serverKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post('https://fcm.googleapis.com/fcm/send', [
                        'registration_ids' => $chunk,
                        'priority' => 'high',
                        'notification' => [
                            'title' => $title,
                            'body' => $message,
                        ],
                        'data' => $stringData,
                        'android' => [
                            'priority' => 'high',
                        ],
                    ]);

                if (! $response->successful()) {
                    $this->logPushFailure('FCM send failed from Laravel panel', $response->status(), $response->json(), $chunk[0] ?? null);
                    continue;
                }

                $responseBody = $response->json() ?: [];
                $results = collect($responseBody['results'] ?? []);
                $successCount = (int) ($responseBody['success'] ?? $results->filter(fn ($item) => empty($item['error']))->count());
                $sent += $successCount;

                foreach ($results as $index => $result) {
                    if (empty($result['error'])) {
                        continue;
                    }

                    $failedToken = $chunk[$index] ?? null;
                    $this->logPushFailure('FCM legacy send rejected token from Laravel panel', $response->status(), $result, $failedToken);
                    $this->deleteInvalidTokenIfNeeded($failedToken, $result);
                }
            } catch (\Throwable $exception) {
                Log::warning('FCM send failed from Laravel panel', [
                    'message' => $exception->getMessage(),
                    'token_suffix' => $this->tokenSuffix($chunk[0] ?? null),
                ]);
            }
        }

        return $sent;
    }

    /*private function loadServiceAccount(): ?array
    {
        $path = trim((string) env('FIREBASE_SERVICE_ACCOUNT_PATH', base_path('../backend/config/firebase-service-account.json')));
        if ($path === '' || ! File::exists($path)) {
            return null;
        }

        try {
            $parsed = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
            if (empty($parsed['client_email']) || empty($parsed['private_key']) || empty($parsed['project_id'])) {
                return null;
            }

            return $parsed;
        } catch (\Throwable $exception) {
            Log::warning('Unable to load Firebase service account in Laravel panel', [
                'message' => $exception->getMessage(),
            ]);
            return null;
        }
    }*/

        private function loadServiceAccount(): ?array
    {
        $path = storage_path('app/schoolcab-fccf5-firebase-adminsdk-fbsvc-71ce85a136.json');

        \Log::info('Firebase Final Debug', [
            'path' => $path,
            'exists' => file_exists($path),
            'readable' => is_readable($path),
        ]);

        if (!file_exists($path)) {
            return null;
        }

        try {
            $parsed = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

            if (empty($parsed['client_email']) || empty($parsed['private_key']) || empty($parsed['project_id'])) {
                return null;
            }

            return $parsed;
        } catch (\Throwable $exception) {
            \Log::warning('Firebase JSON error', [
                'message' => $exception->getMessage(),
            ]);
            return null;
        }
    }

    private function firebaseAccessToken(array $serviceAccount): ?string
    {
        if (self::$cachedAccessToken && self::$cachedAccessTokenExpiresAt?->isFuture()) {
            return self::$cachedAccessToken;
        }

        $issuedAt = now()->timestamp;
        $expiresAt = $issuedAt + 3600;
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $payload = $this->base64UrlEncode(json_encode([
            'iss' => $serviceAccount['client_email'],
            'sub' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $serviceAccount['token_uri'] ?? 'https://oauth2.googleapis.com/token',
            'iat' => $issuedAt,
            'exp' => $expiresAt,
        ], JSON_THROW_ON_ERROR));

        $unsignedToken = $header . '.' . $payload;
        $signature = '';
        openssl_sign($unsignedToken, $signature, $serviceAccount['private_key'], OPENSSL_ALGO_SHA256);
        $assertion = $unsignedToken . '.' . $this->base64UrlEncode($signature);

        try {
            $response = Http::asForm()->timeout(20)->post(
                $serviceAccount['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $assertion,
                ]
            )->json();

            self::$cachedAccessToken = $response['access_token'] ?? null;
            self::$cachedAccessTokenExpiresAt = now()->addSeconds(((int) ($response['expires_in'] ?? 3600)) - 60);
            return self::$cachedAccessToken;
        } catch (\Throwable $exception) {
            Log::warning('Firebase access token request failed in Laravel panel', [
                'message' => $exception->getMessage(),
            ]);
            return null;
        }
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function stringifyData(array $data): array
    {
        return collect($data)
            ->mapWithKeys(fn ($value, $key) => [(string) $key => $value === null ? '' : (string) $value])
            ->all();
    }

    private function deleteInvalidTokenIfNeeded(?string $token, array $payload = []): void
    {
        $token = trim((string) $token);
        if ($token === '' || ! Schema::hasTable('device_tokens')) {
            return;
        }

        $errorCode = strtoupper((string) data_get($payload, 'error.details.0.errorCode', data_get($payload, 'error.status', data_get($payload, 'error', ''))));
        if (! in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT', 'NOT_FOUND', 'INVALID_REGISTRATION'], true)) {
            return;
        }

        DB::table('device_tokens')->where('token', $token)->delete();
    }

    private function logPushFailure(string $message, int $status, array $payload = [], ?string $token = null): void
    {
        Log::warning($message, [
            'status' => $status,
            'token_suffix' => $this->tokenSuffix($token),
            'response' => $payload,
        ]);
    }

    private function tokenSuffix(?string $token): string
    {
        $token = trim((string) $token);
        if ($token === '') {
            return '';
        }

        return substr($token, -12);
    }
}
