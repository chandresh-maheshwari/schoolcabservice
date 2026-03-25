<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\LeaveRequest;
use App\Models\ParentProfile;
use App\Models\Parents;
use App\Models\School;
use App\Models\SupportRequest;
use App\Services\PushNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class MobileRequestController extends Controller
{
    public function __construct(private readonly PushNotificationService $pushNotifications)
    {
    }

    public function leaveIndex(Request $request)
    {
        $panel = $this->resolvePanelContext($request);

        return view('mobile_requests.leave_index', [
            'panel' => $panel,
            'pageTitle' => 'Leave Requests',
            'pageDescription' => 'View leave requests submitted from the transport mobile application.',
        ]);
    }

    public function leaveList(Request $request)
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

        $sortableColumns = ['id', 'school_name', 'child_name', 'parent_name', 'reason', 'from_date', 'to_date', 'submitted_at'];
        if (! in_array($columnName, $sortableColumns, true)) {
            $columnName = 'id';
        }

        $query = LeaveRequest::query()->with(['user', 'child.parent', 'child.school']);
        $this->applyLeavePanelScope($query, $panel, $request);

        $totalRecords = (clone $query)->count();

        if ($searchValue !== '') {
            $query->where(function ($leaveQuery) use ($searchValue) {
                $leaveQuery
                    ->where('reason', 'like', "%{$searchValue}%")
                    ->orWhere('email', 'like', "%{$searchValue}%")
                    ->orWhere('child_name', 'like', "%{$searchValue}%")
                    ->orWhereHas('user', function ($userQuery) use ($searchValue) {
                        $userQuery->where('first_name', 'like', "%{$searchValue}%")
                            ->orWhere('last_name', 'like', "%{$searchValue}%")
                            ->orWhere('email', 'like', "%{$searchValue}%")
                            ->orWhere('mobile', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('child', function ($childQuery) use ($searchValue) {
                        $childQuery->where('child_name', 'like', "%{$searchValue}%")
                            ->orWhereHas('parent', function ($parentQuery) use ($searchValue) {
                                $parentQuery->where('father_name', 'like', "%{$searchValue}%")
                                    ->orWhere('mother_name', 'like', "%{$searchValue}%")
                                    ->orWhere('contact_number', 'like', "%{$searchValue}%")
                                    ->orWhere('email', 'like', "%{$searchValue}%");
                            })
                            ->orWhereHas('school', function ($schoolQuery) use ($searchValue) {
                                $schoolQuery->where('school_name', 'like', "%{$searchValue}%");
                            });
                    });
            });
        }

        $totalRecordwithFilter = (clone $query)->count();

        $requests = $query->get()->map(function (LeaveRequest $leaveRequest) {
            $profile = $this->findParentProfileForUser((int) $leaveRequest->user_id);
            $child = $leaveRequest->child;
            $parent = $child?->parent;
            $user = $leaveRequest->user;

            return [
                'id' => $leaveRequest->id,
                'child_name' => $child?->child_name ?: ($leaveRequest->child_name ?: '-'),
                'parent_name' => $this->resolveRequesterName($profile, $parent, $user),
                'school_name' => $child?->school?->school_name ?? '-',
                'reason' => $leaveRequest->reason ?: '-',
                'from_date' => $leaveRequest->from_date ? Carbon::parse($leaveRequest->from_date)->format('d M Y') : '-',
                'to_date' => $leaveRequest->to_date ? Carbon::parse($leaveRequest->to_date)->format('d M Y') : '-',
                'submitted_at' => $leaveRequest->createdAt ? Carbon::parse($leaveRequest->createdAt)->format('d M Y, h:i A') : '-',
            ];
        });

        $requests = $this->sortLeaveRecords($requests, $columnName, $columnSortOrder)
            ->slice($row, $rowperpage)
            ->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecordwithFilter,
            'data' => $requests,
        ]);
    }

    public function destroyLeave(Request $request, $schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $panel = $this->resolvePanelContext($request);

        $query = LeaveRequest::query();
        $this->applyLeavePanelScope($query, $panel, $request);

        $leaveRequest = $query->findOrFail($id);
        $leaveRequest->delete();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Leave request deleted successfully.',
            ]);
        }

        return back()->with('success', 'Leave request deleted successfully.');
    }

    public function reviewLeave(Request $request, $schoolSlugOrId, $id = null): RedirectResponse
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $panel = $this->resolvePanelContext($request);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected', 'requested'])],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $query = LeaveRequest::query();
        if ($panel['school_id']) {
            $query->whereHas('child', function ($childQuery) use ($panel) {
                $childQuery->where('school_id', $panel['school_id']);
            });
        }

        $leaveRequest = $query->findOrFail($id);
        $this->fillReviewFields($leaveRequest, $validated['status'], $validated['admin_notes'] ?? null);
        $leaveRequest->save();

        $this->pushNotifications->sendToUsers(
            [(int) $leaveRequest->user_id],
            'Leave request updated',
            'Your leave request status is now ' . strtoupper($validated['status']) . '.',
            'leave_request',
            [
                'leaveRequestId' => (int) $leaveRequest->id,
                'status' => $validated['status'],
            ]
        );

        return back()->with('success', 'Leave request updated successfully.');
    }

    public function supportIndex(Request $request)
    {
        $panel = $this->resolvePanelContext($request);
        $query = SupportRequest::query()->with(['user', 'reviewer']);

        if ($panel['school_id']) {
            $this->applySupportSchoolScope($query, $panel['school_id']);
        } elseif ($request->filled('school_id')) {
            $this->applySupportSchoolScope($query, (int) $request->input('school_id'));
        }

        $status = trim((string) $request->input('status'));
        if ($status !== '') {
            $query->where('status', $status);
        }

        $search = trim((string) $request->input('search'));
        if ($search !== '') {
            $query->where(function ($supportQuery) use ($search) {
                $supportQuery
                    ->where('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%");
                    });
            });
        }

        $requests = $query->latest('id')->paginate(12);
        $requests = $this->hydrateSupportRequestContext($requests);

        return view('mobile_requests.support_index', [
            'panel' => $panel,
            'pageTitle' => 'Support Requests',
            'pageDescription' => 'Track parent-raised support tickets and close the loop from the panel.',
            'requests' => $requests,
            'statusOptions' => [
                'open' => 'Open',
                'in_progress' => 'In Progress',
                'closed' => 'Closed',
            ],
            'schoolOptions' => $this->schoolOptions($panel),
        ]);
    }

    public function reviewSupport(Request $request, $schoolSlugOrId, $id = null): RedirectResponse
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $panel = $this->resolvePanelContext($request);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['open', 'in_progress', 'closed'])],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $query = SupportRequest::query();
        if ($panel['school_id']) {
            $this->applySupportSchoolScope($query, $panel['school_id']);
        }

        $supportRequest = $query->findOrFail($id);
        $this->fillReviewFields($supportRequest, $validated['status'], $validated['admin_notes'] ?? null);
        $supportRequest->save();

        $this->pushNotifications->sendToUsers(
            [(int) $supportRequest->user_id],
            'Support request updated',
            'Your support request "' . ($supportRequest->subject ?: 'ticket') . '" is now ' . strtoupper($validated['status']) . '.',
            'support_request',
            [
                'supportRequestId' => (int) $supportRequest->id,
                'status' => $validated['status'],
            ]
        );

        return back()->with('success', 'Support request updated successfully.');
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

    private function schoolOptions(array $panel): Collection
    {
        $query = School::query()
            ->where(function ($schoolQuery) {
                $schoolQuery->where('deleted', 0)->orWhereNull('deleted');
            })
            ->orderBy('school_name');

        if ($panel['school_id']) {
            $query->where('id', $panel['school_id']);
        }

        return $query->get(['id', 'school_name']);
    }

    private function applyLeavePanelScope($query, array $panel, Request $request): void
    {
        if ($panel['school_id']) {
            $query->whereHas('child', function ($childQuery) use ($panel) {
                $childQuery->where('school_id', $panel['school_id']);
            });
        } elseif ($request->filled('school_id')) {
            $schoolId = (int) $request->input('school_id');
            if ($schoolId > 0) {
                $query->whereHas('child', function ($childQuery) use ($schoolId) {
                    $childQuery->where('school_id', $schoolId);
                });
            }
        }
    }

    private function sortLeaveRecords(Collection $records, string $columnName, string $direction): Collection
    {
        $sorted = $records->sortBy(function (array $row) use ($columnName) {
            return match ($columnName) {
                'school_name' => mb_strtolower((string) $row['school_name']),
                'child_name' => mb_strtolower((string) $row['child_name']),
                'parent_name' => mb_strtolower((string) $row['parent_name']),
                'reason' => mb_strtolower((string) $row['reason']),
                'from_date' => (string) $row['from_date'],
                'to_date' => (string) $row['to_date'],
                'submitted_at' => (string) $row['submitted_at'],
                default => (int) $row['id'],
            };
        });

        return $direction === 'desc' ? $sorted->reverse()->values() : $sorted->values();
    }

    private function fillReviewFields($model, string $status, ?string $notes): void
    {
        $model->status = $status;

        if (Schema::hasColumn($model->getTable(), 'admin_notes')) {
            $model->admin_notes = $notes;
        }

        if (Schema::hasColumn($model->getTable(), 'reviewed_by')) {
            $model->reviewed_by = Auth::id();
        }

        if (Schema::hasColumn($model->getTable(), 'reviewed_at')) {
            $model->reviewed_at = now();
        }
    }

    private function findParentProfileForUser(int $userId): ?ParentProfile
    {
        if ($userId <= 0) {
            return null;
        }

        return ParentProfile::query()->where('user_id', $userId)->first();
    }

    private function resolveRequesterName(?ParentProfile $profile, ?Parents $parent, $user): string
    {
        $profileName = trim((string) ($profile->full_name ?? ''));
        if ($profileName !== '') {
            return $profileName;
        }

        $legacyParentName = trim((string) collect([
            $parent->father_name ?? null,
            $parent->mother_name ?? null,
        ])->filter()->join(' / '));
        if ($legacyParentName !== '') {
            return $legacyParentName;
        }

        $userName = trim((string) collect([
            $user->first_name ?? null,
            $user->last_name ?? null,
        ])->filter()->join(' '));

        return $userName !== '' ? $userName : 'Parent User';
    }

    private function resolveRequesterContact(?ParentProfile $profile, ?Parents $parent, $user): string
    {
        $candidates = [
            $profile->phone_number ?? null,
            $profile->emergency_contact ?? null,
            $parent->contact_number ?? null,
            $parent->alternative_contact_number ?? null,
            $user->mobile ?? null,
        ];

        foreach ($candidates as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return '-';
    }

    private function applySupportSchoolScope($query, int $schoolId): void
    {
        $query->whereExists(function ($parentQuery) use ($schoolId) {
            $parentQuery->select(DB::raw(1))
                ->from('parents as p')
                ->join('children as c', 'c.parent_id', '=', 'p.id')
                ->where(function ($visibilityQuery) {
                    $visibilityQuery->whereColumn('p.login_user_id', 'support_requests.user_id');

                    if (Schema::hasColumn('parents', 'user_id')) {
                        $visibilityQuery->orWhereColumn('p.user_id', 'support_requests.user_id');
                    }
                })
                ->where('c.school_id', $schoolId)
                ->where(function ($deletedQuery) {
                    $deletedQuery->where('p.deleted', 0)->orWhereNull('p.deleted');
                })
                ->where(function ($deletedQuery) {
                    $deletedQuery->where('c.deleted', 0)->orWhereNull('c.deleted');
                });
        });
    }

    private function hydrateSupportRequestContext(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $items = $paginator->getCollection();
        $userIds = $items->pluck('user_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();

        $profiles = ParentProfile::query()
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');

        $parentsQuery = Parents::query()->where(function ($deletedQuery) {
            $deletedQuery->where('deleted', 0)->orWhereNull('deleted');
        });
        $parentsQuery->where(function ($linkQuery) use ($userIds) {
            $linkQuery->whereIn('login_user_id', $userIds);

            if (Schema::hasColumn('parents', 'user_id')) {
                $linkQuery->orWhereIn('user_id', $userIds);
            }
        });

        $parents = $parentsQuery->get();
        $parentsByUserId = [];
        foreach ($parents as $parent) {
            foreach (['login_user_id', 'user_id'] as $column) {
                $userId = (int) ($parent->{$column} ?? 0);
                if ($userId > 0 && ! isset($parentsByUserId[$userId])) {
                    $parentsByUserId[$userId] = $parent;
                }
            }
        }

        $children = Child::query()
            ->with('school')
            ->whereIn('parent_id', $parents->pluck('id')->all())
            ->where(function ($deletedQuery) {
                $deletedQuery->where('deleted', 0)->orWhereNull('deleted');
            })
            ->get()
            ->groupBy('parent_id');

        $items->transform(function (SupportRequest $supportRequest) use ($profiles, $parentsByUserId, $children) {
            $userId = (int) $supportRequest->user_id;
            $profile = $profiles->get($userId);
            $parent = $parentsByUserId[$userId] ?? null;
            $childCollection = $parent ? ($children->get($parent->id) ?? collect()) : collect();
            $user = $supportRequest->user;

            $supportRequest->requester_name = $this->resolveRequesterName($profile, $parent, $user);
            $supportRequest->requester_contact = $this->resolveRequesterContact($profile, $parent, $user);
            $supportRequest->school_name = $childCollection
                ->map(fn ($child) => $child->school?->school_name)
                ->filter()
                ->unique()
                ->values()
                ->join(', ') ?: '-';

            $childNames = $childCollection->pluck('child_name')->filter()->unique()->values();
            $supportRequest->child_summary = $childNames->take(3)->join(', ');
            if ($childNames->count() > 3) {
                $supportRequest->child_summary .= ' +' . ($childNames->count() - 3) . ' more';
            }
            if ($supportRequest->child_summary === '') {
                $supportRequest->child_summary = '-';
            }

            return $supportRequest;
        });

        $paginator->setCollection($items);
        return $paginator;
    }
}
