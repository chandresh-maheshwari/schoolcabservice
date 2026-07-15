<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use App\Models\MobileNotification;
use App\Models\School;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PushNotificationController extends Controller
{
    public function __construct(private readonly PushNotificationService $pushNotifications)
    {
    }

    public function index(Request $request)
    {
        MobileNotification::pruneExpiredRecords();

        $panel = $this->resolvePanelContext($request);
        $schools = School::query()
            ->where(function ($query) {
                $query->where('deleted', 0)->orWhereNull('deleted');
            })
            ->when($panel['school_id'], fn ($query) => $query->where('id', $panel['school_id']))
            ->orderBy('school_name')
            ->get(['id', 'school_name']);

        $recentNotifications = collect();
        if (Schema::hasTable('mobile_notifications')) {
            $notificationColumns = Schema::getColumnListing('mobile_notifications');
            $messageColumn = in_array('message', $notificationColumns, true) ? 'notifications.message' : 'notifications.body';
            $createdColumn = in_array('created_at', $notificationColumns, true) ? 'notifications.created_at' : 'notifications.createdAt';

            $recentNotifications = DB::table('mobile_notifications as notifications')
                ->leftJoin('users', 'users.id', '=', 'notifications.user_id')
                ->select([
                    'notifications.id',
                    'notifications.title',
                    DB::raw("COALESCE({$messageColumn}, '') as message"),
                    'notifications.type',
                    DB::raw("{$createdColumn} as created_at_value"),
                    'users.email',
                    'users.first_name',
                    'users.last_name',
                ])
                ->when($panel['school_id'], fn ($query) => $query->whereIn('notifications.user_id', $this->parentUserIdsForSchool($panel['school_id'])))
                ->orderByDesc('notifications.id')
                ->limit(50)
                ->get();
        }

        return view('push_notifications.index', [
            'panel' => $panel,
            'pageTitle' => 'Push Notifications',
            'pageDescription' => 'Manage manual mobile pushes and control automated transport notification templates.',
            'schools' => $schools,
            'settings' => $this->pushNotifications->settings(),
            'recentNotifications' => $recentNotifications,
        ]);
    }


    
    public function notificationList(Request $request)
    {
        MobileNotification::pruneExpiredRecords();

        $panel = $this->resolvePanelContext($request);

        $draw = (int) $request->input('sEcho');
        $row = (int) $request->input('iDisplayStart', 0);
        $rowperpage = (int) $request->input('iDisplayLength', 25);
        $indexColumn = (int) $request->input('iSortCol_0', 0);
        $columnName = $request->input('mDataProp_' . $indexColumn, 'id');
        $columnSortOrder = in_array($request->input('sSortDir_0'), ['asc', 'desc'], true)
            ? $request->input('sSortDir_0')
            : 'desc';
        $searchValue = trim((string) $request->input('sSearch'));

        $sortableColumns = ['id', 'recipient', 'title', 'message', 'type', 'created_at_value'];
        if (! in_array($columnName, $sortableColumns, true)) {
            $columnName = 'id';
        }

        if (! Schema::hasTable('mobile_notifications')) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $notificationColumns = Schema::getColumnListing('mobile_notifications');
        $messageColumn = in_array('message', $notificationColumns, true) ? 'notifications.message' : 'notifications.body';
        $createdColumn = in_array('created_at', $notificationColumns, true) ? 'notifications.created_at' : 'notifications.createdAt';

        $query = DB::table('mobile_notifications as notifications')
            ->leftJoin('users', 'users.id', '=', 'notifications.user_id')
            ->select([
                'notifications.id',
                'notifications.title',
                DB::raw("COALESCE({$messageColumn}, '') as message"),
                'notifications.type',
                DB::raw("{$createdColumn} as created_at_value"),
                'users.email',
                'users.first_name',
                'users.last_name',
            ])
            ->when(
                $panel['school_id'],
                fn ($builder) => $builder->whereIn('notifications.user_id', $this->parentUserIdsForSchool($panel['school_id']))
            );

        $records = collect($query->get())->map(function ($notification) {
            $recipient = trim(((string) ($notification->first_name ?? '')) . ' ' . ((string) ($notification->last_name ?? '')));
            if ($recipient === '') {
                $recipient = (string) ($notification->email ?: '-');
            }

            return [
                'id' => (int) $notification->id,
                'recipient' => strip_tags($recipient),
                'title' => strip_tags((string) ($notification->title ?? '-')),
                'message' => strip_tags((string) ($notification->message ?? '-')),
                'type' => strip_tags((string) ($notification->type ?: 'general')),
                'created_at_sort' => $notification->created_at_value
                    ? Carbon::parse($notification->created_at_value)->timestamp
                    : 0,
                'created_at_value' => $notification->created_at_value
                    ? Carbon::parse($notification->created_at_value)->format('d M Y, h:i A')
                    : '-',
            ];
        });

        $totalRecords = $records->count();

        if ($searchValue !== '') {
            $needle = mb_strtolower($searchValue);
            $records = $records->filter(function (array $notification) use ($needle) {
                foreach (['recipient', 'title', 'message', 'type', 'created_at_value'] as $field) {
                    if (str_contains(mb_strtolower((string) ($notification[$field] ?? '')), $needle)) {
                        return true;
                    }
                }

                return str_contains((string) ($notification['id'] ?? ''), $needle);
            })->values();
        }

        $totalRecordwithFilter = $records->count();
        $sortKey = $columnName === 'created_at_value' ? 'created_at_sort' : $columnName;
        $records = $records
            ->sortBy($sortKey, SORT_NATURAL | SORT_FLAG_CASE, $columnSortOrder === 'desc')
            ->slice($row, $rowperpage)
            ->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecordwithFilter,
            'data' => $records,
        ]);
    }

    public function send(Request $request, $schoolSlug = null): RedirectResponse
    {
        $panel = $this->resolvePanelContext($request);

        $validated = $request->validate([
            'audience' => ['required', 'in:parents,all_mobile_users'],
            'school_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $schoolId = $panel['school_id'] ?: (int) ($validated['school_id'] ?? 0);
        $userIds = $validated['audience'] === 'parents'
            ? $this->parentUserIdsForSchool($schoolId)
            : $this->mobileUserIdsForSchool($schoolId);

        $result = $this->pushNotifications->sendToUsers(
            $userIds,
            $validated['title'],
            $validated['message'],
            'manual_admin_push',
            [
                'source' => 'admin_panel',
                'schoolId' => $schoolId > 0 ? $schoolId : null,
                'audience' => $validated['audience'],
            ]
        );

        $matchedTokens = (int) ($result['matched_tokens'] ?? 0);
        $sentDevices = (int) ($result['sent'] ?? 0);
        $targetedUsers = (int) ($result['targeted_users'] ?? count($userIds));
        $storedUsers = (int) ($result['stored'] ?? 0);

        $flashType = 'success';
        $flashMessage = "Push queued for {$storedUsers} users. Matched {$matchedTokens} device tokens and successfully sent to {$sentDevices} devices.";

        if ($targetedUsers === 0) {
            $flashType = 'warning';
            $flashMessage = 'No eligible mobile users were found for the selected audience.';
        } elseif ($matchedTokens === 0) {
            $flashType = 'warning';
            $flashMessage = "Push was saved for {$storedUsers} users, but no active device tokens matched the selected audience. The inbox notification will still appear inside the app.";
        } elseif ($sentDevices === 0) {
            $flashType = 'warning';
            $flashMessage = "Push matched {$matchedTokens} device tokens but Firebase did not confirm delivery. Please check Laravel and backend logs for the exact FCM error.";
        } elseif ($sentDevices < $matchedTokens) {
            $flashType = 'warning';
            $flashMessage = "Push saved for {$storedUsers} users, matched {$matchedTokens} tokens, and sent to {$sentDevices} devices. Some tokens were rejected or delivery failed.";
        }

        return back()
            ->withInput($request->only(['audience', 'school_id', 'title', 'message']))
            ->with($flashType, $flashMessage);
    }

    public function updateSettings(Request $request, $schoolSlug = null): RedirectResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.enabled' => ['nullable'],
            'settings.*.title_template' => ['required', 'string', 'max:150'],
            'settings.*.message_template' => ['required', 'string', 'max:500'],
        ]);

        $settings = [];
        foreach ($validated['settings'] as $eventKey => $config) {
            $settings[$eventKey] = [
                'enabled' => ! empty($config['enabled']),
                'title_template' => $config['title_template'],
                'message_template' => $config['message_template'],
            ];
        }

        $this->pushNotifications->saveSettings($settings);

        return back()->with('success', 'Push notification settings updated successfully.');
    }

    public function destroy(Request $request, $schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $panel = $this->resolvePanelContext($request);

        $query = MobileNotification::query()->whereKey($id);
        $this->applyNotificationPanelScope($query, $panel);

        $notification = $query->firstOrFail();
        $notification->delete();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Push notification deleted successfully.',
            ]);
        }

        return back()->with('success', 'Push notification deleted successfully.');
    }

    public function multiDelete(Request $request)
    {
        $panel = $this->resolvePanelContext($request);
        $ids = collect($request->input('ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        if ($ids->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No IDs provided',
            ]);
        }

        $query = MobileNotification::query()->whereIn('id', $ids->all());
        $this->applyNotificationPanelScope($query, $panel);
        $deleted = $query->delete();

        return response()->json([
            'success' => $deleted > 0,
            'message' => $deleted > 0
                ? 'Selected push notifications deleted successfully.'
                : 'No push notifications matched the selected IDs.',
        ]);
    }

    public function listMobileNotifications(Request $request)
    {
        MobileNotification::pruneExpiredRecords();

        $user = $this->resolveMobileUserByEmail($request->query('email'));
        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $notificationUserIds = $this->notificationUserIdsForUser($user);
        $notifications = MobileNotification::query()
            ->whereIn('user_id', $notificationUserIds)
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->filter(fn (MobileNotification $notification) => $this->mobileNotificationVisibleToUser($notification, $user))
            ->map(function (MobileNotification $notification) {
                return [
                    'id' => (int) $notification->id,
                    'title' => (string) ($notification->title ?? ''),
                    'message' => (string) ($notification->body ?? $notification->message ?? ''),
                    'type' => (string) ($notification->type ?? 'general'),
                    'isRead' => (bool) ($notification->is_read ?? false),
                    'data' => $notification->payload ?? $notification->data,
                    'createdAt' => optional($notification->created_at ?? $notification->createdAt ?? $notification->sent_at)->toIso8601String(),
                ];
            })
            ->values();

        return response()->json($notifications);
    }

    public function markMobileNotificationRead(Request $request, $id)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = $this->resolveMobileUserByEmail($validated['email']);
        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $notification = MobileNotification::query()
            ->where('id', (int) $id)
            ->whereIn('user_id', $this->notificationUserIdsForUser($user))
            ->first();

        if (! $notification || ! $this->mobileNotificationVisibleToUser($notification, $user)) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $updatePayload = ['is_read' => true];
        $columns = Schema::hasTable('mobile_notifications')
            ? Schema::getColumnListing('mobile_notifications')
            : [];

        if (in_array('updated_at', $columns, true)) {
            $updatePayload['updated_at'] = now();
        } elseif (in_array('updatedAt', $columns, true)) {
            $updatePayload['updatedAt'] = now();
        }

        DB::table('mobile_notifications')
            ->where('id', (int) $notification->id)
            ->update($updatePayload);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    public function registerMobileDevice(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'platform' => ['required', 'string', 'max:50'],
            'token' => ['required', 'string', 'max:2048'],
            'installationId' => ['nullable', 'string', 'max:255'],
        ]);

        $resolvedEmail = trim((string) ($validated['email'] ?? ''));
        $user = $this->resolveMobileUserByEmail($resolvedEmail);
        $this->upsertDeviceToken($user, $resolvedEmail, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Device token registered for future push delivery',
        ]);
    }

    public function unregisterMobileDevice(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['nullable', 'string', 'max:2048'],
            'installationId' => ['nullable', 'string', 'max:255'],
        ]);

        if (trim((string) ($validated['token'] ?? '')) === '' && trim((string) ($validated['installationId'] ?? '')) === '') {
            return response()->json(['message' => 'Token or installationId is required'], 422);
        }

        $resolvedEmail = trim((string) ($validated['email'] ?? ''));
        $user = $this->resolveMobileUserByEmail($resolvedEmail);
        $this->removeDeviceToken($user, $resolvedEmail, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Device token removed from future push delivery',
        ]);
    }

    private function resolvePanelContext(Request $request): array
    {
        $user = Auth::user();
        $isSchoolPanel = $user && method_exists($user, 'isSchool') && $user->isSchool();
        $schoolSlug = $isSchoolPanel ? trim((string) $request->route('schoolSlug')) : null;
        $schoolId = null;

        if ($isSchoolPanel && $user) {
            $schoolId = (int) School::query()
                ->where(function ($query) {
                    $query->where('deleted', 0)->orWhereNull('deleted');
                })
                ->when($schoolSlug, fn ($query) => $query->where('slug', $schoolSlug), fn ($query) => $query->where('user_id', (int) $user->id))
                ->value('id');
        }

        return [
            'is_school_panel' => $isSchoolPanel,
            'school_slug' => $schoolSlug,
            'school_id' => $schoolId > 0 ? $schoolId : null,
        ];
    }

    private function parentUserIdsForSchool(?int $schoolId): array
    {
        $query = DB::table('children')
            ->join('parents', 'parents.id', '=', 'children.parent_id')
            ->where(function ($query) {
                $query->where('children.deleted', 0)->orWhereNull('children.deleted');
            })
            ->where(function ($query) {
                $query->where('parents.deleted', 0)->orWhereNull('parents.deleted');
            });

        if ($schoolId) {
            $query->where('children.school_id', $schoolId);
        }

        return $query
            ->selectRaw('DISTINCT COALESCE(parents.login_user_id, parents.user_id) as user_id')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();
    }

    private function mobileUserIdsForSchool(?int $schoolId): array
    {
        $parentIds = $this->parentUserIdsForSchool($schoolId);

        if (! $schoolId) {
            $driverIds = DB::table('drivers')
                ->selectRaw('DISTINCT COALESCE(login_user_id, user_id) as user_id')
                ->where(function ($query) {
                    $query->where('deleted', 0)->orWhereNull('deleted');
                })
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->all();

            return collect(array_merge($parentIds, $driverIds))->unique()->values()->all();
        }

        $schoolUserId = (int) School::query()->where('id', $schoolId)->value('user_id');
        $driverIds = DB::table('routes')
            ->join('drivers', 'drivers.id', '=', 'routes.driver_id')
            ->where('routes.user_id', $schoolUserId)
            ->where(function ($query) {
                $query->where('drivers.deleted', 0)->orWhereNull('drivers.deleted');
            })
            ->selectRaw('DISTINCT COALESCE(drivers.login_user_id, drivers.user_id) as user_id')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        return collect(array_merge($parentIds, $driverIds))->unique()->values()->all();
    }

    private function applyNotificationPanelScope($query, array $panel): void
    {
        if ($panel['school_id']) {
            $query->whereIn('user_id', $this->parentUserIdsForSchool($panel['school_id']));
        }
    }

    private function resolveMobileUserByEmail(?string $email): ?User
    {
        $email = trim((string) $email);
        if ($email === '') {
            return null;
        }

        return User::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->where(function ($query) {
                if (Schema::hasColumn('users', 'deleted')) {
                    $query->where('deleted', 0)->orWhereNull('deleted');
                    return;
                }

                $query->whereRaw('1 = 1');
            })
            ->first();
    }

    private function notificationUserIdsForUser(User $user): array
    {
        // Mobile inbox must stay isolated per logged-in parent/driver account.
        // Parent/driver records may also store a shared owner/admin `user_id`,
        // which caused cross-account notifications to appear in other logins.
        // We therefore scope the inbox strictly to the authenticated mobile user id.
        return collect([
            (int) ($user->id ?? 0),
        ])
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function mobileNotificationVisibleToUser(MobileNotification $notification, User $user): bool
    {
        $type = trim((string) ($notification->type ?? ''));
        if ($type !== 'manual_admin_push') {
            return true;
        }

        $payload = $notification->payload;
        if (! is_array($payload)) {
            $payload = $notification->data;
        }
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            $payload = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($payload)) {
            $payload = [];
        }

        $schoolId = (int) data_get($payload, 'schoolId', 0);
        if ($schoolId <= 0) {
            // Do not show broadcast test/manual pushes to every parent/driver inbox.
            return false;
        }

        return in_array($schoolId, $this->mobileUserSchoolIds($user), true);
    }

    private function mobileUserSchoolIds(User $user): array
    {
        $schoolIds = collect();
        $email = mb_strtolower(trim((string) ($user->email ?? '')));

        if (Schema::hasTable('parents') && Schema::hasTable('children')) {
            $parentSchoolIds = DB::table('parents')
                ->join('children', 'children.parent_id', '=', 'parents.id')
                ->where(function ($deletedQuery) {
                    $deletedQuery->where('parents.deleted', 0)->orWhereNull('parents.deleted');
                })
                ->where(function ($deletedQuery) {
                    $deletedQuery->where('children.deleted', 0)->orWhereNull('children.deleted');
                })
                ->where(function ($query) use ($user, $email) {
                    if (Schema::hasColumn('parents', 'login_user_id')) {
                        $query->where('parents.login_user_id', (int) $user->id);
                    } elseif (Schema::hasColumn('parents', 'user_id')) {
                        $query->where('parents.user_id', (int) $user->id);
                    }

                    if ($email !== '' && Schema::hasColumn('parents', 'email')) {
                        $method = Schema::hasColumn('parents', 'login_user_id') || Schema::hasColumn('parents', 'user_id')
                            ? 'orWhereRaw'
                            : 'whereRaw';
                        $query->{$method}('LOWER(parents.email) = ?', [$email]);
                    }
                })
                ->pluck('children.school_id');

            $schoolIds = $schoolIds->merge($parentSchoolIds);
        }

        if (Schema::hasTable('drivers')) {
            $driverSchoolIds = DB::table('drivers')
                ->where(function ($deletedQuery) {
                    $deletedQuery->where('deleted', 0)->orWhereNull('deleted');
                })
                ->where(function ($query) use ($user, $email) {
                    if (Schema::hasColumn('drivers', 'login_user_id')) {
                        $query->where('login_user_id', (int) $user->id);
                    } elseif (Schema::hasColumn('drivers', 'user_id')) {
                        $query->where('user_id', (int) $user->id);
                    }

                    if ($email !== '' && Schema::hasColumn('drivers', 'email')) {
                        $method = Schema::hasColumn('drivers', 'login_user_id') || Schema::hasColumn('drivers', 'user_id')
                            ? 'orWhereRaw'
                            : 'whereRaw';
                        $query->{$method}('LOWER(email) = ?', [$email]);
                    }
                })
                ->pluck('school_id');

            $schoolIds = $schoolIds->merge($driverSchoolIds);
        }

        return $schoolIds
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function upsertDeviceToken(?User $user, string $resolvedEmail, array $payload): void
    {
        if (! Schema::hasTable('device_tokens')) {
            return;
        }

        $columns = Schema::getColumnListing('device_tokens');
        $token = trim((string) ($payload['token'] ?? ''));
        $installationId = trim((string) ($payload['installationId'] ?? ''));
        $platform = trim((string) ($payload['platform'] ?? 'mobile'));
        $resolvedEmail = mb_strtolower(trim($resolvedEmail));
        $resolvedUserId = (int) ($user?->id ?? 0);

        $query = DB::table('device_tokens');
        if ($resolvedUserId > 0 && in_array('user_id', $columns, true)) {
            $query->where('user_id', $resolvedUserId);
        } elseif ($resolvedEmail !== '' && in_array('email', $columns, true)) {
            $query->whereRaw('LOWER(TRIM(email)) = ?', [$resolvedEmail]);
        }

        if ($installationId !== '' && in_array('installation_id', $columns, true)) {
            $query->where('installation_id', $installationId);
        } elseif ($token !== '' && in_array('token', $columns, true)) {
            $query->where('token', $token);
        } elseif ($token !== '' && in_array('device_token', $columns, true)) {
            $query->where('device_token', $token);
        }

        $existing = $query->first();
        $record = [];

        if ($resolvedUserId > 0 && in_array('user_id', $columns, true)) {
            $record['user_id'] = $resolvedUserId;
        }
        if ($resolvedEmail !== '' && in_array('email', $columns, true)) {
            $record['email'] = $resolvedEmail;
        }
        if (in_array('token', $columns, true)) {
            $record['token'] = $token;
        }
        if (in_array('device_token', $columns, true)) {
            $record['device_token'] = $token;
        }
        if (in_array('platform', $columns, true)) {
            $record['platform'] = $platform;
        }
        if (in_array('device_type', $columns, true)) {
            $record['device_type'] = $platform;
        }
        if (in_array('installation_id', $columns, true)) {
            $record['installation_id'] = $installationId !== '' ? $installationId : null;
        }
        if (in_array('is_active', $columns, true)) {
            $record['is_active'] = 1;
        }
        if (in_array('last_used_at', $columns, true)) {
            $record['last_used_at'] = now();
        }
        if (in_array('updated_at', $columns, true)) {
            $record['updated_at'] = now();
        } elseif (in_array('updatedAt', $columns, true)) {
            $record['updatedAt'] = now();
        }
        if (! $existing && in_array('createdAt', $columns, true)) {
            $record['createdAt'] = now();
        }
        if (! $existing && in_array('created_at', $columns, true)) {
            $record['created_at'] = now();
        }

        if ($existing) {
            DB::table('device_tokens')->where('id', $existing->id)->update($record);
            return;
        }

        DB::table('device_tokens')->insert($record);
    }

    private function removeDeviceToken(?User $user, string $resolvedEmail, array $payload): void
    {
        if (! Schema::hasTable('device_tokens')) {
            return;
        }

        $columns = Schema::getColumnListing('device_tokens');
        $token = trim((string) ($payload['token'] ?? ''));
        $installationId = trim((string) ($payload['installationId'] ?? ''));
        $resolvedEmail = mb_strtolower(trim($resolvedEmail));
        $resolvedUserId = (int) ($user?->id ?? 0);

        DB::table('device_tokens')
            ->where(function ($query) use ($columns, $resolvedUserId, $resolvedEmail) {
                if ($resolvedUserId > 0 && in_array('user_id', $columns, true)) {
                    $query->where('user_id', $resolvedUserId);
                }

                if ($resolvedEmail !== '' && in_array('email', $columns, true)) {
                    $method = $resolvedUserId > 0 && in_array('user_id', $columns, true) ? 'orWhereRaw' : 'whereRaw';
                    $query->{$method}('LOWER(TRIM(email)) = ?', [$resolvedEmail]);
                }
            })
            ->where(function ($query) use ($columns, $token, $installationId) {
                if ($installationId !== '' && in_array('installation_id', $columns, true)) {
                    $query->where('installation_id', $installationId);
                }

                if ($token !== '') {
                    if ($query->getQuery()->wheres !== null) {
                        $query->orWhere(function ($nested) use ($columns, $token) {
                            if (in_array('token', $columns, true)) {
                                $nested->where('token', $token);
                            }
                            if (in_array('device_token', $columns, true)) {
                                $method = in_array('token', $columns, true) ? 'orWhere' : 'where';
                                $nested->{$method}('device_token', $token);
                            }
                        });
                    } else {
                        if (in_array('token', $columns, true)) {
                            $query->where('token', $token);
                        }
                        if (in_array('device_token', $columns, true)) {
                            $method = in_array('token', $columns, true) ? 'orWhere' : 'where';
                            $query->{$method}('device_token', $token);
                        }
                    }
                }
            })
            ->delete();
    }
}
