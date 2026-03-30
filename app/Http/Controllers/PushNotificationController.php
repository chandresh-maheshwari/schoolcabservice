<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\PushNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PushNotificationController extends Controller
{
    public function __construct(private readonly PushNotificationService $pushNotifications)
    {
    }

    public function index(Request $request)
    {
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
            $createdColumn = in_array('createdAt', $notificationColumns, true) ? 'notifications.createdAt' : 'notifications.created_at';

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
        $createdColumn = in_array('createdAt', $notificationColumns, true) ? 'notifications.createdAt' : 'notifications.created_at';

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

        return back()->with($flashType, $flashMessage);
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
}
