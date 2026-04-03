<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\LeaveRequest;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use App\Support\PermissionName;

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

        $leaveRequestsHasParentId = Schema::hasColumn('leave_requests', 'parent_id');
        $leaveRelations = ['user', 'child.parent', 'child.school'];
        if ($leaveRequestsHasParentId) {
            $leaveRelations[] = 'parent.children.school';
        }

        $query = LeaveRequest::query()->with($leaveRelations);
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

                if ($leaveRequestsHasParentId) {
                    $leaveQuery->orWhereHas('parent', function ($parentQuery) use ($searchValue) {
                        $parentQuery->where('father_name', 'like', "%{$searchValue}%")
                            ->orWhere('mother_name', 'like', "%{$searchValue}%")
                            ->orWhere('contact_number', 'like', "%{$searchValue}%")
                            ->orWhere('email', 'like', "%{$searchValue}%")
                            ->orWhereHas('children.school', function ($schoolQuery) use ($searchValue) {
                                $schoolQuery->where('school_name', 'like', "%{$searchValue}%");
                            });
                    });
                }
            });
        }

        $totalRecordwithFilter = (clone $query)->count();

        $requests = $query->get()->map(function (LeaveRequest $leaveRequest) {
            $parent = $this->resolveRequestParent($leaveRequest, $leaveRequest->child);
            $child = $leaveRequest->child;
            $user = $leaveRequest->user;

            return [
                'id' => $leaveRequest->id,
                'child_name' => $child?->child_name ?: ($leaveRequest->child_name ?: '-'),
                'parent_name' => $this->resolveRequesterName($parent, $user),
                'school_name' => $this->resolveLeaveSchoolName($leaveRequest, $child, $parent),
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

    public function multiDeleteLeave(Request $request)
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

        $query = LeaveRequest::query()->whereIn('id', $ids->all());
        $this->applyLeavePanelScope($query, $panel, $request);
        $deleted = $query->delete();

        return response()->json([
            'success' => $deleted > 0,
            'message' => $deleted > 0
                ? 'Selected leave requests deleted successfully.'
                : 'No leave requests matched the selected IDs.',
        ]);
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
        $this->applyLeavePanelScope($query, $panel, $request);

        $leaveRequest = $query->findOrFail($id);
        $this->fillReviewFields($leaveRequest, $validated['status'], $validated['admin_notes'] ?? null);
        $leaveRequest->save();

        try {
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
        } catch (\Throwable $exception) {
            Log::warning('Leave request review notification failed.', [
                'leave_request_id' => (int) $leaveRequest->id,
                'error' => $exception->getMessage(),
            ]);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Leave request updated successfully.',
            ]);
        }

        return back()->with('success', 'Leave request updated successfully.');
    }

    public function supportIndex(Request $request)
    {
        $panel = $this->resolvePanelContext($request);
        $user = Auth::user();
        $query = SupportRequest::query()->with(['user', 'reviewer', 'parent.children.school']);
        $supportRequestsHasParentId = Schema::hasColumn('support_requests', 'parent_id');

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

                if ($supportRequestsHasParentId) {
                    $supportQuery->orWhereHas('parent', function ($parentQuery) use ($search) {
                        $parentQuery->where('father_name', 'like', "%{$search}%")
                            ->orWhere('mother_name', 'like', "%{$search}%")
                            ->orWhere('contact_number', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhereHas('children.school', function ($schoolQuery) use ($search) {
                                $schoolQuery->where('school_name', 'like', "%{$search}%");
                            });
                    });
                }
            });
        }

        $requests = $query->latest('id')->paginate(12);
        $requests = $this->hydrateSupportRequestContext($requests);

        return view('mobile_requests.support_index', [
            'panel' => $panel,
            'pageTitle' => 'Support Requests',
            'pageDescription' => 'Track parent-raised support tickets and close the loop from the panel.',
            'requests' => $requests,
            'canReviewSupportRequests' => $user?->canAccessAdminRoute(PermissionName::normalize(
                $panel['is_school_panel'] ? 'school.supportRequests.review' : 'supportRequests.review'
            )) ?? false,
            'canDeleteSupportRequests' => $user?->canAccessAdminRoute(PermissionName::normalize(
                $panel['is_school_panel'] ? 'school.supportRequests.destroy' : 'supportRequests.destroy'
            )) ?? false,
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

        try {
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
        } catch (\Throwable $exception) {
            Log::warning('Support request review notification failed.', [
                'support_request_id' => (int) $supportRequest->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return back()->with('success', 'Support request updated successfully.');
    }

    public function destroySupport(Request $request, $schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);
        $panel = $this->resolvePanelContext($request);

        $query = SupportRequest::query();
        if ($panel['school_id']) {
            $this->applySupportSchoolScope($query, $panel['school_id']);
        }

        $supportRequest = $query->findOrFail($id);
        $supportRequest->delete();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Support request deleted successfully.',
            ]);
        }

        return back()->with('success', 'Support request deleted successfully.');
    }

    public function multiDeleteSupport(Request $request)
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

        $query = SupportRequest::query()->whereIn('id', $ids->all());
        if ($panel['school_id']) {
            $this->applySupportSchoolScope($query, $panel['school_id']);
        }

        $deleted = $query->delete();

        return response()->json([
            'success' => $deleted > 0,
            'message' => $deleted > 0
                ? 'Selected support requests deleted successfully.'
                : 'No support requests matched the selected IDs.',
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
            $this->applyLeaveSchoolScope($query, $panel['school_id']);
        } elseif ($request->filled('school_id')) {
            $schoolId = (int) $request->input('school_id');
            if ($schoolId > 0) {
                $this->applyLeaveSchoolScope($query, $schoolId);
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

    private function resolveRequestParent($requestModel, ?Child $child = null): ?Parents
    {
        if ($requestModel?->relationLoaded('parent') && $requestModel->parent) {
            return $requestModel->parent;
        }

        if ($requestModel?->parent) {
            return $requestModel->parent;
        }

        return $child?->parent;
    }

    private function resolveLeaveSchoolName(LeaveRequest $leaveRequest, ?Child $child, ?Parents $parent): string
    {
        $schoolName = trim((string) ($child?->school?->school_name ?? ''));
        if ($schoolName !== '') {
            return $schoolName;
        }

        $resolvedParent = $parent ?: $this->resolveRequestParent($leaveRequest, $child);
        if ($resolvedParent) {
            $schoolName = $resolvedParent->children
                ->map(fn ($linkedChild) => $linkedChild->school?->school_name)
                ->filter()
                ->unique()
                ->values()
                ->join(', ');
        }

        return $schoolName !== '' ? $schoolName : '-';
    }

    private function resolveRequesterName(?Parents $parent, $user): string
    {
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

    private function resolveRequesterContact(?Parents $parent, $user): string
    {
        $candidates = [
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
        $supportRequestsHasParentId = Schema::hasColumn('support_requests', 'parent_id');
        $parentsHasUserId = Schema::hasColumn('parents', 'user_id');

        $query->whereExists(function ($parentQuery) use ($schoolId, $supportRequestsHasParentId, $parentsHasUserId) {
            $parentQuery->select(DB::raw(1))
                ->from('parents as p')
                ->join('children as c', 'c.parent_id', '=', 'p.id')
                ->where(function ($visibilityQuery) use ($supportRequestsHasParentId, $parentsHasUserId) {
                    if ($supportRequestsHasParentId) {
                        $visibilityQuery->whereColumn('p.id', 'support_requests.parent_id')
                            ->orWhereColumn('p.login_user_id', 'support_requests.user_id');
                    } else {
                        $visibilityQuery->whereColumn('p.login_user_id', 'support_requests.user_id');
                    }

                    if ($parentsHasUserId) {
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
        $parentIds = $items->pluck('parent_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();

        $parentsQuery = Parents::query()->where(function ($deletedQuery) {
            $deletedQuery->where('deleted', 0)->orWhereNull('deleted');
        });
        $parentsQuery->when($parentIds->isNotEmpty() || $userIds->isNotEmpty(), function ($query) use ($userIds, $parentIds) {
            $query->where(function ($linkQuery) use ($userIds, $parentIds) {
                if ($parentIds->isNotEmpty()) {
                    $linkQuery->whereIn('id', $parentIds);
                }

                if ($userIds->isNotEmpty()) {
                    $method = $parentIds->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                    $linkQuery->{$method}('login_user_id', $userIds);
                }

                if (Schema::hasColumn('parents', 'user_id') && $userIds->isNotEmpty()) {
                    $method = ($parentIds->isNotEmpty() || $userIds->isNotEmpty()) ? 'orWhereIn' : 'whereIn';
                    $linkQuery->{$method}('user_id', $userIds);
                }
            });
        });

        $parents = $parentsQuery->get();
        $parentsById = $parents->keyBy('id');
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

        $items->transform(function (SupportRequest $supportRequest) use ($parentsById, $parentsByUserId, $children) {
            $userId = (int) $supportRequest->user_id;
            $parentId = (int) ($supportRequest->parent_id ?? 0);
            $parent = $parentsById[$parentId] ?? ($parentsByUserId[$userId] ?? null);
            $childCollection = $parent ? ($children->get($parent->id) ?? collect()) : collect();
            $user = $supportRequest->user;

            $supportRequest->requester_name = $this->resolveRequesterName($parent, $user);
            $supportRequest->requester_contact = $this->resolveRequesterContact($parent, $user);
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

    private function applyLeaveSchoolScope($query, int $schoolId): void
    {
        $leaveRequestsHasParentId = Schema::hasColumn('leave_requests', 'parent_id');

        $query->where(function ($leaveQuery) use ($schoolId, $leaveRequestsHasParentId) {
            $leaveQuery->whereHas('child', function ($childQuery) use ($schoolId) {
                $childQuery->where('school_id', $schoolId);
            });

            if ($leaveRequestsHasParentId) {
                $leaveQuery->orWhereHas('parent.children', function ($childQuery) use ($schoolId) {
                    $childQuery->where('school_id', $schoolId)
                        ->where(function ($deletedQuery) {
                            $deletedQuery->where('deleted', 0)->orWhereNull('deleted');
                        });
                });
            }
        });
    }
}
