<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PushNotificationService
{
    public const SETTINGS_TABLE = 'push_notification_settings';
    private const PUSH_CHANNEL_ID = 'scb_push_channel_v2';
    private static ?string $cachedAccessToken = null;
    private static ?Carbon $cachedAccessTokenExpiresAt = null;

    public function defaults(): array
    {
        return [
            'vehicle_near_pickup' => [
                'enabled' => true,
                'title_template' => 'Vehicle near pickup stop',
                'message_template' => "{{childName}}'s vehicle is near {{stopLabel}}.",
            ],
            'vehicle_arrived_pickup' => [
                'enabled' => true,
                'title_template' => 'Vehicle arrived at pickup stop',
                'message_template' => "{{childName}}'s vehicle has arrived at {{stopLabel}}.",
            ],
            'child_picked_up' => [
                'enabled' => true,
                'title_template' => 'Child picked up',
                'message_template' => '{{childName}} has been picked up successfully.',
            ],
            'vehicle_near_school' => [
                'enabled' => true,
                'title_template' => 'Vehicle near school',
                'message_template' => "{{childName}}'s vehicle is almost at school.",
            ],
            'vehicle_arrived_school' => [
                'enabled' => true,
                'title_template' => 'Vehicle arrived at school',
                'message_template' => "{{childName}}'s vehicle has reached school.",
            ],
            'child_arrived_school' => [
                'enabled' => true,
                'title_template' => 'Child arrived at school',
                'message_template' => '{{childName}} has arrived at school safely.',
            ],
            'vehicle_near_dropoff' => [
                'enabled' => true,
                'title_template' => 'Vehicle near drop-off stop',
                'message_template' => "{{childName}}'s vehicle is near {{stopLabel}}.",
            ],
            'vehicle_arrived_dropoff' => [
                'enabled' => true,
                'title_template' => 'Vehicle arrived at drop-off stop',
                'message_template' => "{{childName}}'s vehicle has arrived at {{stopLabel}}.",
            ],
            'child_dropped_home' => [
                'enabled' => true,
                'title_template' => 'Child dropped successfully',
                'message_template' => '{{childName}} has been dropped successfully.',
            ],
            'trip_started' => [
                'enabled' => false,
                'title_template' => 'Trip started',
                'message_template' => 'The driver has started the {{tripType}} trip.',
            ],
            'manual_admin_push' => [
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

        foreach ($defaults as $eventKey => $config) {
            DB::table(self::SETTINGS_TABLE)->updateOrInsert(
                ['event_key' => $eventKey],
                [
                    'enabled' => $config['enabled'] ? 1 : 0,
                    'title_template' => $config['title_template'],
                    'message_template' => $config['message_template'],
                    'metadata' => json_encode(['source' => 'default']),
                    'createdAt' => now(),
                    'updatedAt' => now(),
                ]
            );
        }

        $rows = DB::table(self::SETTINGS_TABLE)->get();
        foreach ($rows as $row) {
            $defaults[$row->event_key] = [
                'enabled' => (bool) $row->enabled,
                'title_template' => $row->title_template,
                'message_template' => $row->message_template,
            ];
        }

        return $defaults;
    }

    public function saveSettings(array $settings): void
    {
        if (! Schema::hasTable(self::SETTINGS_TABLE)) {
            return;
        }

        foreach ($settings as $eventKey => $config) {
            DB::table(self::SETTINGS_TABLE)->updateOrInsert(
                ['event_key' => $eventKey],
                [
                    'enabled' => ! empty($config['enabled']) ? 1 : 0,
                    'title_template' => (string) ($config['title_template'] ?? ''),
                    'message_template' => (string) ($config['message_template'] ?? ''),
                    'updatedAt' => now(),
                    'createdAt' => now(),
                ]
            );
        }
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
                if (in_array('updatedAt', $notificationTableColumns, true)) {
                    $payload['updatedAt'] = now();
                }
                if (in_array('created_at', $notificationTableColumns, true)) {
                    $payload['created_at'] = now();
                }
                if (in_array('updated_at', $notificationTableColumns, true)) {
                    $payload['updated_at'] = now();
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

        return DB::table('device_tokens')
            ->whereIn('user_id', $userIds)
            ->orderByDesc('updatedAt')
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
