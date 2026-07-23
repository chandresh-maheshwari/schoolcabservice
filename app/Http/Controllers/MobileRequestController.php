<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\LeaveRequest;
use App\Models\Parents;
use App\Models\Route;
use App\Models\School;
use App\Models\StopPickup;
use App\Models\SupportRequest;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use App\Support\PermissionName;

class MobileRequestController extends Controller
{
    public function __construct(private readonly PushNotificationService $pushNotifications)
    {
    }

    public function listParentSupportRequests(Request $request)
    {
        $user = $this->resolveMobileUserByEmail($request->query('email'));
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $requests = SupportRequest::query()
            ->where('user_id', (int) $user->id)
            ->latest('id')
            ->get()
            ->map(fn (SupportRequest $supportRequest) => [
                'id' => (int) $supportRequest->id,
                'category' => (string) ($supportRequest->category ?? ''),
                'subject' => (string) ($supportRequest->subject ?? ''),
                'message' => (string) ($supportRequest->message ?? ''),
                'status' => (string) ($supportRequest->status ?? ''),
                'createdAt' => optional($supportRequest->createdAt)->toIso8601String(),
                'updatedAt' => optional($supportRequest->updated_at)->toIso8601String(),
            ])
            ->values();

        return response()->json($requests);
    }

    public function createParentSupportRequest(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'category' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $user = $this->resolveMobileUserByEmail($validated['email']);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $parent = $this->resolveMobileParentProfile((int) $user->id, $validated['email']);
        $supportRequestId = $this->insertMobileRequestRecord('support_requests', [
            'user_id' => (int) $user->id,
            'parent_id' => $parent?->id,
            'email' => (string) $user->email,
            'category' => trim((string) $validated['category']),
            'subject' => trim((string) $validated['subject']),
            'message' => trim((string) $validated['message']),
            'status' => 'open',
        ]);
        $supportRequest = SupportRequest::query()->findOrFail($supportRequestId);

        $this->notifyPanelUsersForMobileRequest(
            userId: (int) $user->id,
            parent: $parent,
            title: 'New support request',
            message: sprintf(
                '%s submitted "%s" in %s.',
                (string) $user->email,
                (string) $supportRequest->subject,
                (string) $supportRequest->category
            ),
            type: 'support_request',
            payload: [
                'supportRequestId' => (int) $supportRequest->id,
                'userId' => (int) $user->id,
                'parentId' => $parent?->id,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Support request created successfully',
            'data' => [
                'id' => (int) $supportRequest->id,
                'category' => (string) $supportRequest->category,
                'subject' => (string) $supportRequest->subject,
                'message' => (string) $supportRequest->message,
                'status' => (string) $supportRequest->status,
                'createdAt' => optional($supportRequest->createdAt)->toIso8601String(),
            ],
        ], 201);
    }

    public function listMobileSchools(Request $request): JsonResponse
    {
        $panel = $this->resolvePanelContext($request);

        $schools = $this->schoolOptions($panel)
            ->map(fn (School $school) => [
                'id' => (int) $school->id,
                'schoolName' => (string) ($school->school_name ?? ''),
            ])
            ->filter(fn (array $school) => trim($school['schoolName']) !== '')
            ->values();

        return response()->json($schools);
    }

    public function listParentLeaveRequests(Request $request)
    {
        $user = $this->resolveMobileUserByEmail($request->query('email'));
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $requests = LeaveRequest::query()
            ->where('user_id', (int) $user->id)
            ->latest('id')
            ->get()
            ->map(fn (LeaveRequest $leaveRequest) => [
                'id' => (int) $leaveRequest->id,
                'childId' => $leaveRequest->child_id ? (int) $leaveRequest->child_id : null,
                'childName' => (string) ($leaveRequest->child_name ?? ''),
                'fromDate' => $leaveRequest->from_date ? $leaveRequest->from_date->format('Y-m-d') : null,
                'toDate' => $leaveRequest->to_date ? $leaveRequest->to_date->format('Y-m-d') : null,
                'reason' => (string) ($leaveRequest->reason ?? ''),
                'status' => (string) ($leaveRequest->status ?? ''),
                'createdAt' => optional($leaveRequest->createdAt)->toIso8601String(),
                'updatedAt' => optional($leaveRequest->updated_at)->toIso8601String(),
            ])
            ->values();

        return response()->json($requests);
    }

    public function createParentLeaveRequest(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'childId' => ['nullable', 'integer'],
            'childName' => ['required', 'string', 'max:255'],
            'fromDate' => ['required', 'date'],
            'toDate' => ['required', 'date', 'after_or_equal:fromDate'],
            'reason' => ['required', 'string', 'max:5000'],
        ]);

        $user = $this->resolveMobileUserByEmail($validated['email']);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $parent = $this->resolveMobileParentProfile((int) $user->id, $validated['email']);
        $child = null;
        if (! empty($validated['childId'])) {
            $child = $this->resolveMobileParentChild((int) $validated['childId'], (int) $user->id, $parent);
            if (! $child) {
                Log::warning('Mobile leave request child mapping failed. Falling back to childName-only save.', [
                    'user_id' => (int) $user->id,
                    'child_id' => (int) $validated['childId'],
                    'child_name' => (string) $validated['childName'],
                ]);
            }
        }

        $leaveRequestId = $this->insertMobileRequestRecord('leave_requests', [
            'user_id' => (int) $user->id,
            'parent_id' => $parent?->id,
            'email' => (string) $user->email,
            'child_id' => $child?->id,
            'child_name' => trim((string) ($child?->child_name ?? $validated['childName'])),
            'from_date' => $validated['fromDate'],
            'to_date' => $validated['toDate'],
            'reason' => trim((string) $validated['reason']),
            'status' => 'requested',
        ]);
        $leaveRequest = LeaveRequest::query()->findOrFail($leaveRequestId);

        $this->notifyPanelUsersForMobileRequest(
            userId: (int) $user->id,
            parent: $parent,
            title: 'New leave request',
            message: sprintf(
                '%s leave request submitted for %s to %s.',
                (string) $leaveRequest->child_name,
                (string) optional($leaveRequest->from_date)->format('Y-m-d'),
                (string) optional($leaveRequest->to_date)->format('Y-m-d')
            ),
            type: 'leave_request',
            payload: [
                'leaveRequestId' => (int) $leaveRequest->id,
                'userId' => (int) $user->id,
                'parentId' => $parent?->id,
                'childId' => $child?->id,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Leave request created successfully',
            'data' => [
                'id' => (int) $leaveRequest->id,
                'childId' => $child?->id ? (int) $child->id : null,
                'childName' => (string) $leaveRequest->child_name,
                'fromDate' => optional($leaveRequest->from_date)->format('Y-m-d'),
                'toDate' => optional($leaveRequest->to_date)->format('Y-m-d'),
                'reason' => (string) $leaveRequest->reason,
                'status' => (string) $leaveRequest->status,
                'createdAt' => optional($leaveRequest->createdAt)->toIso8601String(),
            ],
        ], 201);
    }

    public function getParentProfile(Request $request)
    {
        $user = $this->resolveMobileUserByEmail($request->query('email'));
        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $parent = $this->resolveMobileParentProfile((int) $user->id, (string) $user->email);
        $profile = $this->loadMobileParentProfileRecord((int) $user->id);

        return response()->json([
            'success' => true,
            'data' => $this->mapMobileParentProfileResponse($request, $profile, $parent, $user),
        ]);
    }

    public function getChildTripHistory(Request $request, int $child): JsonResponse
    {
        $user = $this->resolveMobileUserByEmail($request->query('email'));
        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $parent = $this->resolveMobileParentProfile((int) $user->id, (string) $user->email);
        $childRecord = $this->resolveMobileParentChild($child, (int) $user->id, $parent);
        if (! $childRecord) {
            return response()->json(['message' => 'Child not found'], 404);
        }

        if (! Schema::hasTable('trips')) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $routeId = (int) ($childRecord->route_id ?? 0);
        if ($routeId <= 0) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $routeColumn = Schema::hasColumn('trips', 'routeId')
            ? 'routeId'
            : (Schema::hasColumn('trips', 'route_id') ? 'route_id' : null);
        $createdColumn = Schema::hasColumn('trips', 'createdAt')
            ? 'createdAt'
            : (Schema::hasColumn('trips', 'created_at') ? 'created_at' : 'id');

        $query = DB::table('trips');
        if ($routeColumn) {
            $query->where($routeColumn, $routeId);
        }

        $trips = $query
            ->orderByDesc($createdColumn)
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $route = $this->resolveRouteForMobileParentChild($childRecord);
        $pickupLabel = $this->firstNonEmptyString(
            $this->resolveMobileStopPickupLabel((string) ($childRecord->today_pickup_name ?? '')),
            $this->resolveMobileStopPickupLabel((string) ($childRecord->pickup_name ?? '')),
            (string) ($childRecord->pickup_name ?? '')
        );
        $dropLabel = $this->firstNonEmptyString(
            $this->resolveMobileStopPickupLabel((string) ($childRecord->stop_name ?? '')),
            (string) ($childRecord->school?->school_name ?? ''),
            'School'
        );
        $routeEndpointLabel = $this->resolveMobileRouteEndpointLabel($route);

        $items = $trips->map(function ($trip) use ($childRecord, $route, $pickupLabel, $dropLabel, $routeEndpointLabel) {
            $tripType = strtolower((string) ($trip->tripType ?? $trip->trip_type ?? 'morning'));
            $tripType = $tripType === 'afternoon' ? 'afternoon' : 'morning';
            $stops = $this->decodeMobileTripStops($trip->stops ?? null);
            $childStop = $this->findMobileTripChildStop($stops, (int) $childRecord->id, $tripType);
            $childStopLabel = $this->firstNonEmptyString(
                data_get($childStop, 'stopLabel'),
                data_get($childStop, 'pickupName'),
                data_get($childStop, 'name')
            );

            $fromLabel = $tripType === 'afternoon'
                ? $this->firstNonEmptyString($routeEndpointLabel, $route?->name, 'School')
                : $this->firstNonEmptyString($childStopLabel, $pickupLabel);
            $toLabel = $tripType === 'afternoon'
                ? $this->firstNonEmptyString($childStopLabel, $pickupLabel)
                : $this->firstNonEmptyString($routeEndpointLabel, $dropLabel, $route?->name, 'School');

            return [
                'id' => (int) ($trip->id ?? 0),
                'childId' => (int) $childRecord->id,
                'childName' => (string) ($childRecord->child_name ?? 'Child'),
                'tripType' => $tripType,
                'status' => $this->firstNonEmptyString(data_get($childStop, 'status'), $trip->status ?? null, 'waiting'),
                'routeName' => (string) ($route?->name ?? ''),
                'driverName' => (string) ($route?->driver?->driver_name ?? ''),
                'pickupLabel' => $fromLabel,
                'dropLabel' => $toLabel,
                'stops' => $this->mapMobileTripTimelineStops($stops, (int) $childRecord->id, $tripType),
                'startedAt' => $this->mobileIsoDate($trip->createdAt ?? $trip->created_at ?? null),
                'updatedAt' => $this->mobileIsoDate($trip->updated_at ?? $trip->updatedAt ?? null),
            ];
        })->values()->all();

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function getChildRouteStops(Request $request, int $child): JsonResponse
    {
        $user = $this->resolveMobileUserByEmail($request->query('email'));
        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $parent = $this->resolveMobileParentProfile((int) $user->id, (string) $user->email);
        $childRecord = $this->resolveMobileParentChild($child, (int) $user->id, $parent);
        if (! $childRecord) {
            return response()->json(['message' => 'Child not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->buildMobileChildRouteStopOptions($childRecord),
        ]);
    }

    public function saveParentProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['nullable', 'email'],
            'fullName' => ['nullable', 'string', 'max:255'],
            'motherName' => ['nullable', 'string', 'max:255'],
            'phoneNumber' => ['nullable', 'string', 'max:30'],
            'alternatePhone' => ['nullable', 'string', 'max:30'],
            'homeAddress' => ['nullable', 'string', 'max:5000'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'pincode' => ['nullable', 'string', 'max:20'],
            'emergencyContact' => ['nullable', 'string', 'max:30'],
            'profileImageUrl' => ['nullable', 'string', 'max:5000'],
            'profileImageBase64' => ['nullable', 'string'],
            'profileImageName' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'profileImage' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first() ?: 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $resolvedEmail = trim((string) ($validated['email'] ?? $request->query('email') ?? ''));
        $user = $resolvedEmail !== ''
            ? $this->resolveMobileUserByEmail($resolvedEmail)
            : $this->resolveActor($request);
        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $resolvedEmail = trim((string) ($resolvedEmail !== '' ? $resolvedEmail : ($user->email ?? '')));
        $parent = $this->resolveMobileParentProfile((int) $user->id, $resolvedEmail);

        $profileImageUrl = trim((string) ($validated['profileImageUrl'] ?? ''));
        $uploadedProfileImage = $request->file('profileImage')
            ?: $request->file('photo')
            ?: $request->file('image');

        if ($uploadedProfileImage) {
            $storedPhoto = $this->storeLoginUserPhotoFromUpload($uploadedProfileImage, (int) $user->id);
            if (! $storedPhoto) {
                throw ValidationException::withMessages([
                    'profileImage' => ['Unable to store uploaded profile image.'],
                ]);
            }

            $profileImageUrl = asset('storage/' . ltrim($storedPhoto, '/'));
        } elseif (trim((string) ($validated['profileImageBase64'] ?? '')) !== '') {
            $profileImageUrl = $this->storeMobileParentProfileImage(
                $request,
                (int) $user->id,
                (string) $validated['profileImageBase64'],
                $validated['profileImageName'] ?? null
            );
        }

        if (Schema::hasTable('parents') && $parent) {
            $parentUpdates = [];

            if (Schema::hasColumn('parents', 'father_name')) {
                $parentUpdates['father_name'] = $validated['fullName'] ?? null;
            } elseif (Schema::hasColumn('parents', 'parent_name')) {
                $parentUpdates['parent_name'] = $validated['fullName'] ?? null;
            } elseif (Schema::hasColumn('parents', 'name')) {
                $parentUpdates['name'] = $validated['fullName'] ?? null;
            }

            if (Schema::hasColumn('parents', 'mother_name')) {
                $parentUpdates['mother_name'] = $validated['motherName'] ?? null;
            }
            if (Schema::hasColumn('parents', 'contact_number')) {
                $parentUpdates['contact_number'] = $validated['phoneNumber'] ?? null;
            } elseif (Schema::hasColumn('parents', 'parent_phone')) {
                $parentUpdates['parent_phone'] = $validated['phoneNumber'] ?? null;
            } elseif (Schema::hasColumn('parents', 'mobile')) {
                $parentUpdates['mobile'] = $validated['phoneNumber'] ?? null;
            }
            if (Schema::hasColumn('parents', 'alternative_contact_number')) {
                $parentUpdates['alternative_contact_number'] = $validated['alternatePhone'] ?? null;
            }
            if (Schema::hasColumn('parents', 'address')) {
                $parentUpdates['address'] = $validated['homeAddress'] ?? null;
            }
            if (Schema::hasColumn('parents', 'address_1')) {
                $parentUpdates['address_1'] = $validated['homeAddress'] ?? null;
            }
            if (Schema::hasColumn('parents', 'city')) {
                $parentUpdates['city'] = $validated['city'] ?? null;
            }
            if (Schema::hasColumn('parents', 'state')) {
                $parentUpdates['state'] = $validated['state'] ?? null;
            }
            if (Schema::hasColumn('parents', 'pincode')) {
                $parentUpdates['pincode'] = $validated['pincode'] ?? null;
            }
            if (Schema::hasColumn('parents', 'emergency_phone')) {
                $parentUpdates['emergency_phone'] = $validated['emergencyContact'] ?? null;
            }

            if ($parentUpdates !== []) {
                DB::table('parents')->where('id', (int) $parent->id)->update($parentUpdates);
            }
        }

        if (Schema::hasTable('parent_profiles')) {
            $columns = Schema::getColumnListing('parent_profiles');
            $record = [];

            if (in_array('user_id', $columns, true)) {
                $record['user_id'] = (int) $user->id;
            }
            if (in_array('parent_id', $columns, true)) {
                $record['parent_id'] = $parent?->id;
            }
            if (in_array('email', $columns, true)) {
                $record['email'] = (string) $user->email;
            }
            if (in_array('full_name', $columns, true)) {
                $record['full_name'] = $validated['fullName'] ?? null;
            }
            if (in_array('mother_name', $columns, true)) {
                $record['mother_name'] = $validated['motherName'] ?? null;
            }
            if (in_array('phone_number', $columns, true)) {
                $record['phone_number'] = $validated['phoneNumber'] ?? null;
            }
            if (in_array('alternate_phone', $columns, true)) {
                $record['alternate_phone'] = $validated['alternatePhone'] ?? null;
            }
            if (in_array('alternate_mobile', $columns, true)) {
                $record['alternate_mobile'] = $validated['alternatePhone'] ?? null;
            }
            if (in_array('home_address', $columns, true)) {
                $record['home_address'] = $validated['homeAddress'] ?? null;
            }
            if (in_array('address', $columns, true)) {
                $record['address'] = $validated['homeAddress'] ?? null;
            }
            if (in_array('city', $columns, true)) {
                $record['city'] = $validated['city'] ?? null;
            }
            if (in_array('state', $columns, true)) {
                $record['state'] = $validated['state'] ?? null;
            }
            if (in_array('pincode', $columns, true)) {
                $record['pincode'] = $validated['pincode'] ?? null;
            }
            if (in_array('emergency_contact', $columns, true)) {
                $record['emergency_contact'] = $validated['emergencyContact'] ?? null;
            }
            if (in_array('emergency_phone', $columns, true)) {
                $record['emergency_phone'] = $validated['emergencyContact'] ?? null;
            }
            if (in_array('profile_image_url', $columns, true)) {
                $record['profile_image_url'] = $profileImageUrl !== '' ? $profileImageUrl : null;
            }
            if (in_array('mobile', $columns, true)) {
                $record['mobile'] = $validated['phoneNumber'] ?? null;
            }
            if (in_array('parent_name', $columns, true)) {
                $record['parent_name'] = $validated['fullName'] ?? null;
            }
            if (in_array('updated_at', $columns, true)) {
                $record['updated_at'] = now();
            } elseif (in_array('updatedAt', $columns, true)) {
                $record['updatedAt'] = now();
            }

            $existing = DB::table('parent_profiles')->where('user_id', (int) $user->id)->first();
            if ($existing) {
                DB::table('parent_profiles')->where('id', $existing->id)->update($record);
            } else {
                if (in_array('createdAt', $columns, true)) {
                    $record['createdAt'] = now();
                }
                if (in_array('created_at', $columns, true)) {
                    $record['created_at'] = now();
                }
                DB::table('parent_profiles')->insert($record);
            }
        }

        if ($profileImageUrl !== '' && Schema::hasTable('users') && Schema::hasColumn('users', 'photo')) {
            $relativePhoto = preg_replace('#^https?://[^/]+/#', '', $profileImageUrl);
            DB::table('users')->where('id', (int) $user->id)->update([
                'photo' => $relativePhoto ?: $profileImageUrl,
            ]);
        }

        $refreshedParent = $this->resolveMobileParentProfile((int) $user->id, $resolvedEmail);
        $refreshedProfile = $this->loadMobileParentProfileRecord((int) $user->id);
        $refreshedUser = User::query()->find((int) $user->id) ?? $user;

        $responseData = $this->mapMobileParentProfileResponse($request, $refreshedProfile, $refreshedParent, $refreshedUser);
        if (trim((string) ($responseData['profileImageUrl'] ?? '')) === '' && $profileImageUrl !== '') {
            $responseData['profileImageUrl'] = $this->mobileAbsoluteUrl($request, $profileImageUrl);
        }

        return response()->json([
            'success' => true,
            'message' => 'Parent profile saved successfully',
            'data' => $responseData,
        ]);
    }

    public function deleteParentChild(Request $request, Child $child): JsonResponse
    {
        $user = $this->resolveMobileUserByEmail($request->query('email'));
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $parent = $this->resolveMobileParentProfile((int) $user->id, (string) $user->email);
        $ownedChild = $this->resolveMobileParentChild((int) $child->id, (int) $user->id, $parent);

        if (! $ownedChild) {
            return response()->json([
                'success' => false,
                'message' => 'Child not found for this parent.',
            ], 404);
        }

        $ownedChild->deleted = 1;
        $ownedChild->save();

        return response()->json([
            'success' => true,
            'message' => 'Child deleted successfully.',
        ]);
    }

    public function getEmergencyContacts(Request $request)
    {
        $user = $this->resolveMobileUserByEmail($request->query('email'));
        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->resolveManagedEmergencyContactsForUser($user),
        ]);
    }

    public function saveEmergencyContacts(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'schoolContact' => ['nullable', 'string', 'max:30'],
            'transportContact' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $user = $this->resolveMobileUserByEmail($validated['email']);
        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (! $user->isAdmin()) {
            return response()->json([
                'message' => 'Emergency contacts are managed by the school/admin and cannot be changed from the parent app.',
            ], 403);
        }

        if (
            ! Schema::hasTable('emergency_contacts') ||
            ! Schema::hasColumn('emergency_contacts', 'user_id') ||
            ! Schema::hasColumn('emergency_contacts', 'school_contact') ||
            ! Schema::hasColumn('emergency_contacts', 'transport_contact')
        ) {
            return response()->json([
                'message' => 'Emergency contact storage is not available in this deployment.',
            ], 400);
        }

        $columns = Schema::getColumnListing('emergency_contacts');
        $record = [
            'user_id' => (int) $user->id,
            'school_contact' => $validated['schoolContact'] ?? null,
            'transport_contact' => $validated['transportContact'] ?? null,
        ];

        if (in_array('email', $columns, true)) {
            $record['email'] = (string) $user->email;
        }
        if (in_array('notes', $columns, true)) {
            $record['notes'] = $validated['notes'] ?? null;
        }
        if (in_array('updated_at', $columns, true)) {
            $record['updated_at'] = now();
        } elseif (in_array('updatedAt', $columns, true)) {
            $record['updatedAt'] = now();
        }

        $existing = DB::table('emergency_contacts')->where('user_id', (int) $user->id)->first();
        if ($existing) {
            DB::table('emergency_contacts')->where('id', $existing->id)->update($record);
        } else {
            if (in_array('createdAt', $columns, true)) {
                $record['createdAt'] = now();
            }
            if (in_array('created_at', $columns, true)) {
                $record['created_at'] = now();
            }
            DB::table('emergency_contacts')->insert($record);
        }

        try {
            $this->pushNotifications->sendToUsers(
                [(int) $user->id],
                'Emergency contacts updated',
                'Emergency contact details were updated by the admin panel.',
                'emergency',
                ['managedBy' => 'admin']
            );
        } catch (\Throwable $exception) {
            Log::warning('Emergency contacts notification failed.', [
                'user_id' => (int) $user->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Emergency contacts saved successfully',
            'data' => $this->resolveManagedEmergencyContactsForUser($user),
        ]);
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
            $query->where(function ($leaveQuery) use ($searchValue, $leaveRequestsHasParentId) {
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
                'status' => (string) ($leaveRequest->status ?? 'requested'),
                'submitted_at' => $leaveRequest->createdAt ? Carbon::parse($leaveRequest->createdAt)->format('d M Y, h:i A') : '-',
            ];
        });

        $requests = $this->sortLeaveRecords($requests, $columnName, $columnSortOrder)
            ->slice($row, $rowperpage)
            ->values();

        return response()->json([
            'draw' => $draw,
            'sEcho' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecordwithFilter,
            'iTotalRecords' => $totalRecords,
            'iTotalDisplayRecords' => $totalRecordwithFilter,
            'data' => $requests,
            'aaData' => $requests,
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

    public function reviewLeave(Request $request, $schoolSlugOrId, $id = null): RedirectResponse|JsonResponse
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
        $parentsHasEmail = Schema::hasColumn('parents', 'email');
        $supportRequestsHasEmail = Schema::hasColumn('support_requests', 'email');

        $query->whereExists(function ($parentQuery) use ($schoolId, $supportRequestsHasParentId, $parentsHasUserId, $parentsHasEmail, $supportRequestsHasEmail) {
            $parentQuery->select(DB::raw(1))
                ->from('parents as p')
                ->join('children as c', 'c.parent_id', '=', 'p.id')
                ->where(function ($visibilityQuery) use ($supportRequestsHasParentId, $parentsHasUserId, $parentsHasEmail, $supportRequestsHasEmail) {
                    if ($supportRequestsHasParentId) {
                        $visibilityQuery->whereColumn('p.id', 'support_requests.parent_id')
                            ->orWhereColumn('p.login_user_id', 'support_requests.user_id');
                    } else {
                        $visibilityQuery->whereColumn('p.login_user_id', 'support_requests.user_id');
                    }

                    if ($parentsHasUserId) {
                        $visibilityQuery->orWhereColumn('p.user_id', 'support_requests.user_id');
                    }

                    if ($parentsHasEmail && $supportRequestsHasEmail) {
                        $visibilityQuery->orWhereRaw(
                            'LOWER(TRIM(p.email)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(support_requests.email)) COLLATE utf8mb4_unicode_ci'
                        );
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
        $emails = $items->pluck('email')
            ->map(fn ($email) => mb_strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values();

        $parentsQuery = Parents::query()->where(function ($deletedQuery) {
            $deletedQuery->where('deleted', 0)->orWhereNull('deleted');
        });
        $parentsQuery->when($parentIds->isNotEmpty() || $userIds->isNotEmpty() || $emails->isNotEmpty(), function ($query) use ($userIds, $parentIds, $emails) {
            $query->where(function ($linkQuery) use ($userIds, $parentIds, $emails) {
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

                if (Schema::hasColumn('parents', 'email') && $emails->isNotEmpty()) {
                    $method = ($parentIds->isNotEmpty() || $userIds->isNotEmpty()) ? 'orWhereRaw' : 'whereRaw';
                    $placeholders = implode(',', array_fill(0, $emails->count(), '?'));
                    $linkQuery->{$method}('LOWER(email) IN (' . $placeholders . ')', $emails->all());
                }
            });
        });

        $parents = $parentsQuery->get();
        $parentsById = $parents->keyBy('id');
        $parentsByUserId = [];
        $parentsByEmail = [];
        foreach ($parents as $parent) {
            foreach (['login_user_id', 'user_id'] as $column) {
                $userId = (int) ($parent->{$column} ?? 0);
                if ($userId > 0 && ! isset($parentsByUserId[$userId])) {
                    $parentsByUserId[$userId] = $parent;
                }
            }

            $email = mb_strtolower(trim((string) ($parent->email ?? '')));
            if ($email !== '' && ! isset($parentsByEmail[$email])) {
                $parentsByEmail[$email] = $parent;
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
            $email = mb_strtolower(trim((string) ($supportRequest->email ?? '')));
            $parent = $parentsById[$parentId]
                ?? ($parentsByUserId[$userId] ?? null)
                ?? ($email !== '' ? ($parentsByEmail[$email] ?? null) : null);
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
        $parentsHasUserId = Schema::hasColumn('parents', 'user_id');
        $parentsHasEmail = Schema::hasColumn('parents', 'email');
        $leaveRequestsHasEmail = Schema::hasColumn('leave_requests', 'email');

        $query->where(function ($leaveQuery) use ($schoolId, $leaveRequestsHasParentId, $parentsHasUserId, $parentsHasEmail, $leaveRequestsHasEmail) {
            $leaveQuery->whereHas('child', function ($childQuery) use ($schoolId) {
                $childQuery->where('school_id', $schoolId);
            });

            $leaveQuery->orWhere(function ($unresolvedLeaveQuery) use ($schoolId, $leaveRequestsHasParentId, $parentsHasUserId, $parentsHasEmail, $leaveRequestsHasEmail) {
                $unresolvedLeaveQuery
                    ->where(function ($childIdQuery) {
                        $childIdQuery->whereNull('leave_requests.child_id')
                            ->orWhere('leave_requests.child_id', 0);
                    })
                    ->whereExists(function ($parentQuery) use ($schoolId, $leaveRequestsHasParentId, $parentsHasUserId, $parentsHasEmail, $leaveRequestsHasEmail) {
                        $parentQuery->select(DB::raw(1))
                            ->from('parents as p')
                            ->join('children as c', 'c.parent_id', '=', 'p.id')
                            ->where(function ($visibilityQuery) use ($leaveRequestsHasParentId, $parentsHasUserId, $parentsHasEmail, $leaveRequestsHasEmail) {
                                if ($leaveRequestsHasParentId) {
                                    $visibilityQuery->whereColumn('p.id', 'leave_requests.parent_id')
                                        ->orWhereColumn('p.login_user_id', 'leave_requests.user_id');
                                } else {
                                    $visibilityQuery->whereColumn('p.login_user_id', 'leave_requests.user_id');
                                }

                                if ($parentsHasUserId) {
                                    $visibilityQuery->orWhereColumn('p.user_id', 'leave_requests.user_id');
                                }

                                if ($parentsHasEmail && $leaveRequestsHasEmail) {
                                    $visibilityQuery->orWhereRaw(
                                        'LOWER(TRIM(p.email)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(leave_requests.email)) COLLATE utf8mb4_unicode_ci'
                                    );
                                }
                            })
                            ->where('c.school_id', $schoolId)
                            ->whereRaw(
                                'LOWER(TRIM(c.child_name)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(leave_requests.child_name)) COLLATE utf8mb4_unicode_ci'
                            )
                            ->where(function ($deletedQuery) {
                                $deletedQuery->where('p.deleted', 0)->orWhereNull('p.deleted');
                            })
                            ->where(function ($deletedQuery) {
                                $deletedQuery->where('c.deleted', 0)->orWhereNull('c.deleted');
                            });
                    });
            });
        });
    }

    private function resolveMobileUserByEmail(?string $email): ?User
    {
        $email = trim((string) $email);
        if ($email === '') {
            return null;
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->where(function ($query) {
                if (Schema::hasColumn('users', 'deleted')) {
                    $query->where('deleted', 0)->orWhereNull('deleted');
                    return;
                }

                $query->whereRaw('1 = 1');
            })
            ->first();

        if ($user) {
            return $user;
        }

        if (! Schema::hasTable('parents')) {
            return null;
        }

        $parent = Parents::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->where(function ($query) {
                if (Schema::hasColumn('parents', 'deleted')) {
                    $query->where('deleted', 0)->orWhereNull('deleted');
                    return;
                }

                $query->whereRaw('1 = 1');
            })
            ->latest('id')
            ->first();

        if (! $parent) {
            return null;
        }

        $linkedUserId = (int) ($parent->login_user_id ?? $parent->user_id ?? 0);
        if ($linkedUserId <= 0) {
            return null;
        }

        return User::query()
            ->where('id', $linkedUserId)
            ->where(function ($query) {
                if (Schema::hasColumn('users', 'deleted')) {
                    $query->where('deleted', 0)->orWhereNull('deleted');
                    return;
                }

                $query->whereRaw('1 = 1');
            })
            ->first();
    }

    private function resolveMobileParentProfile(int $userId, ?string $email = null): ?Parents
    {
        $email = trim((string) $email);
        $baseQuery = fn () => Parents::query()
            ->with(['children.school'])
            ->withCount('children')
            ->where(function ($deletedQuery) {
                $deletedQuery->where('deleted', 0)->orWhereNull('deleted');
            });

        $loginQuery = $baseQuery()->where(function ($parentQuery) use ($userId) {
            $parentQuery->where('login_user_id', $userId);

            if (Schema::hasColumn('parents', 'user_id')) {
                $parentQuery->orWhere('user_id', $userId);
            }
        });

        $parent = (clone $loginQuery)
            ->orderByDesc('children_count')
            ->latest('id')
            ->first();
        if ($parent) {
            return $parent;
        }

        if ($email === '') {
            return null;
        }

        return $baseQuery()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->orderByDesc('children_count')
            ->latest('id')
            ->first();
    }

    private function resolveMobileParentChild(int $childId, int $userId, ?Parents $parent): ?Child
    {
        $query = Child::query()
            ->with('school')
            ->where('id', $childId)
            ->where(function ($deletedQuery) {
                $deletedQuery->where('deleted', 0)->orWhereNull('deleted');
            });

        if ($parent) {
            $query->where('parent_id', (int) $parent->id);
            return $query->first();
        }

        return $query->whereExists(function ($parentQuery) use ($userId) {
            $parentQuery->select(DB::raw(1))
                ->from('parents')
                ->whereColumn('parents.id', 'children.parent_id')
                ->where(function ($ownershipQuery) use ($userId) {
                    $ownershipQuery->where('parents.login_user_id', $userId);

                    if (Schema::hasColumn('parents', 'user_id')) {
                        $ownershipQuery->orWhere('parents.user_id', $userId);
                    }
                })
                ->where(function ($deletedQuery) {
                    $deletedQuery->where('parents.deleted', 0)->orWhereNull('parents.deleted');
                });
        })->first();
    }

    private function resolvePanelRecipientUserIdsForParent(?Parents $parent, int $userId): array
    {
        $recipientIds = User::query()
            ->get()
            ->filter(fn (User $candidate) => method_exists($candidate, 'isAdmin') && $candidate->isAdmin())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($parent && ! $parent->relationLoaded('children')) {
            $parent->loadMissing('children.school');
        }

        $schoolUserIds = $parent
            ? $parent->children
                ->map(fn ($child) => (int) ($child->school->user_id ?? 0))
                ->filter(fn ($id) => $id > 0)
                ->all()
            : [];

        $recipientIds[] = $userId;

        return array_values(array_unique(array_filter([
            ...$recipientIds,
            ...$schoolUserIds,
        ], fn ($id) => (int) $id > 0)));
    }

    private function notifyPanelUsersForMobileRequest(int $userId, ?Parents $parent, string $title, string $message, string $type, array $payload = []): void
    {
        $recipientIds = $this->resolvePanelRecipientUserIdsForParent($parent, $userId);
        if ($recipientIds === []) {
            return;
        }

        try {
            $this->pushNotifications->sendToUsers(
                $recipientIds,
                $title,
                $message,
                $type,
                $payload
            );
        } catch (\Throwable $exception) {
            Log::warning('Mobile request notification failed.', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function insertMobileRequestRecord(string $table, array $payload): int
    {
        $columns = Schema::getColumnListing($table);
        $record = [];

        foreach ($payload as $column => $value) {
            if (in_array($column, $columns, true)) {
                $record[$column] = $value;
            }
        }

        if (in_array('createdAt', $columns, true) && ! array_key_exists('createdAt', $record)) {
            $record['createdAt'] = now();
        }
        if (in_array('created_at', $columns, true) && ! array_key_exists('created_at', $record)) {
            $record['created_at'] = now();
        }
        if (in_array('updated_at', $columns, true) && ! array_key_exists('updated_at', $record)) {
            $record['updated_at'] = now();
        } elseif (in_array('updatedAt', $columns, true) && ! array_key_exists('updatedAt', $record)) {
            $record['updatedAt'] = now();
        }

        return (int) DB::table($table)->insertGetId($record);
    }

    private function loadMobileParentProfileRecord(int $userId): ?object
    {
        if (! Schema::hasTable('parent_profiles')) {
            return null;
        }

        return DB::table('parent_profiles')->where('user_id', $userId)->first();
    }

    private function mapMobileParentProfileResponse(Request $request, ?object $profile, ?Parents $parent, User $user): array
    {
        $children = $parent?->relationLoaded('children')
            ? $parent->children
            : ($parent?->children()->with(['school', 'route.driver', 'route.vehicle'])->get() ?? collect());

        if ($children instanceof Collection && method_exists($children, 'loadMissing')) {
            $children = $children->loadMissing(['school', 'route.driver', 'route.vehicle']);
        } else {
            $children = collect($children);
        }

        $userFullName = trim((string) collect([
            $user->first_name ?? null,
            $user->last_name ?? null,
        ])->filter()->join(' '));

        $userPhoto = trim((string) ($user->photo ?? ''));
        $defaultUserPhoto = trim((string) $this->defaultUserPhotoPath());
        $preferredProfileImage = $this->firstNonEmptyString(
            data_get($profile, 'profile_image_url'),
            data_get($profile, 'profileImageUrl')
        );

        if ($preferredProfileImage === '' && $userPhoto !== '' && $userPhoto !== $defaultUserPhoto) {
            $preferredProfileImage = $userPhoto;
        }

        $response = [
            'email' => (string) ($user->email ?? data_get($profile, 'email') ?? data_get($parent, 'email') ?? ''),
            'fullName' => $this->firstNonEmptyString(
                data_get($parent, 'parent_name'),
                data_get($parent, 'father_name'),
                data_get($parent, 'name'),
                data_get($profile, 'full_name'),
                data_get($profile, 'fullName'),
                data_get($profile, 'parent_name'),
                $userFullName
            ),
            'motherName' => $this->firstNonEmptyString(
                data_get($parent, 'mother_name'),
                data_get($profile, 'mother_name'),
                data_get($profile, 'motherName')
            ),
            'phoneNumber' => $this->firstNonEmptyString(
                data_get($parent, 'parent_phone'),
                data_get($parent, 'contact_number'),
                data_get($parent, 'mobile'),
                data_get($profile, 'phone_number'),
                data_get($profile, 'phoneNumber'),
                data_get($profile, 'parent_phone'),
                $user->mobile ?? null
            ),
            'alternatePhone' => $this->firstNonEmptyString(
                data_get($parent, 'alternative_contact_number'),
                data_get($profile, 'alternate_phone'),
                data_get($profile, 'alternatePhone'),
                data_get($profile, 'alternate_mobile')
            ),
            'homeAddress' => $this->firstNonEmptyString(
                data_get($parent, 'address'),
                collect([data_get($parent, 'address_1'), data_get($parent, 'address_2')])->filter()->join(', '),
                data_get($profile, 'home_address'),
                data_get($profile, 'homeAddress'),
                data_get($profile, 'address')
            ),
            'city' => $this->firstNonEmptyString(data_get($parent, 'city'), data_get($profile, 'city')),
            'state' => $this->firstNonEmptyString(data_get($parent, 'state'), data_get($profile, 'state')),
            'pincode' => $this->firstNonEmptyString(data_get($parent, 'pincode'), data_get($profile, 'pincode')),
            'emergencyContact' => $this->firstNonEmptyString(
                data_get($parent, 'emergency_phone'),
                data_get($parent, 'alternative_contact_number'),
                data_get($profile, 'emergency_contact'),
                data_get($profile, 'emergencyContact'),
                data_get($profile, 'emergency_phone')
            ),
            'profileImageUrl' => $this->mobileAbsoluteUrl(
                $request,
                $preferredProfileImage
            ),
        ];

        if ($children->isNotEmpty()) {
            $response['children'] = $children
                ->map(fn (Child $child) => $this->mapMobileParentChildResponse($child))
                ->values()
                ->all();
        }

        return $response;
    }

    private function resolveCancelledChildIdsForActiveTrips(array $childIds): array
    {
        $childIds = collect($childIds)
            ->map(fn ($id) => is_numeric($id) ? (int) $id : null)
            ->filter(fn ($id) => ! is_null($id) && $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($childIds) || ! Schema::hasTable('trips') || ! Schema::hasColumn('trips', 'status')) {
            return [];
        }

        $tripRows = DB::table('trips')
            ->select('stops')
            ->where('status', 'running')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $cancelledChildIds = [];

        foreach ($tripRows as $tripRow) {
            $stops = $tripRow->stops;
            if (is_string($stops)) {
                $decoded = json_decode($stops, true);
                $stops = is_array($decoded) ? $decoded : [];
            }

            if (! is_array($stops)) {
                continue;
            }

            foreach ($stops as $stop) {
                if (! is_array($stop)) {
                    continue;
                }

                $childId = (int) ($stop['childId'] ?? $stop['child_id'] ?? 0);
                if ($childId <= 0 || ! in_array($childId, $childIds, true)) {
                    continue;
                }

                $skippedReason = strtolower(trim((string) ($stop['skippedReason'] ?? '')));
                $isCancelled = ($stop['skipped'] ?? false) === true
                    && in_array($skippedReason, ['child_absent', 'pickup_cancelled'], true);

                if ($isCancelled) {
                    $cancelledChildIds[] = $childId;
                }
            }
        }

        return array_values(array_unique($cancelledChildIds));
    }

    private function mapMobileParentChildResponse(Child $child): array
    {
        $route = $this->resolveRouteForMobileParentChild($child);
        $routeJson = is_array($route?->route_json ?? null) ? $route->route_json : [];
        $effectiveRouteId = (int) ($route?->id ?? $child->route_id ?? 0);
        $tripActive = $this->resolveMobileParentChildTripActive($effectiveRouteId);
        $pickupPin = $tripActive ? $this->resolveMobileParentChildPickupPin($child) : '';
        $pickupPinActive = $tripActive && trim($pickupPin) !== '';
        $pickupName = (string) ($child->pickup_name ?? '');
        $todayPickupName = (string) ($child->today_pickup_name ?? '');
        $stopName = (string) ($child->stop_name ?? '');
        $pickupLabel = $this->resolveMobileStopPickupLabel($pickupName, false);
        $stopLabel = $this->resolveMobileStopPickupLabel($stopName, true);
        $todayPickupLabel = $this->resolveMobileStopPickupLabel($todayPickupName, false);

        return [
            'id' => (int) $child->id,
            'childName' => (string) ($child->child_name ?? ''),
            'className' => (string) ($child->class ?? ''),
            'class' => (string) ($child->class ?? ''),
            'schoolId' => (int) ($child->school_id ?? 0),
            'schoolName' => (string) ($child->school?->school_name ?? ''),
            'schoolAddress' => $this->firstNonEmptyString(
                $child->school_address ?? null,
                $child->school?->address ?? null
            ),
            'pickupName' => $pickupName,
            'pickupLabel' => $pickupLabel,
            'pickup_label' => $pickupLabel,
            'stopName' => $stopName,
            'stopLabel' => $stopLabel,
            'stop_label' => $stopLabel,
            'todayPickupName' => $todayPickupName,
            'today_pickup_name' => $todayPickupName,
            'todayPickupLabel' => $todayPickupLabel,
            'today_pickup_label' => $todayPickupLabel,
            'todayPickupDate' => (string) ($child->today_pickup_date ?? ''),
            'today_pickup_date' => (string) ($child->today_pickup_date ?? ''),
            'secretPin' => $pickupPin,
            'secret_pin' => $pickupPin,
            'pickupPinActive' => $pickupPinActive,
            'pickup_pin_active' => $pickupPinActive,
            'tripActive' => $tripActive,
            'trip_active' => $tripActive,
            'routeId' => $effectiveRouteId,
            'routeName' => (string) ($route?->name ?? ''),
            'route' => $this->mapMobileParentRouteResponse($route, $routeJson),
        ];
    }

    private function resolveMobileStopPickupLabel(?string $stopPickupId, bool $preferStopName = false): string
    {
        $normalizedId = trim((string) $stopPickupId);
        if ($normalizedId === '' || ! ctype_digit($normalizedId) || ! Schema::hasTable('stops_pickup')) {
            return '';
        }

        $stop = StopPickup::query()
            ->where('id', (int) $normalizedId)
            ->where(function ($query) {
                $query->where('deleted', 0)->orWhereNull('deleted');
            })
            ->first(['pickup_name', 'stop_name']);

        return $preferStopName
            ? $this->firstNonEmptyString($stop?->stop_name ?? null, $stop?->pickup_name ?? null)
            : $this->firstNonEmptyString($stop?->pickup_name ?? null, $stop?->stop_name ?? null);
    }

    private function buildMobileChildRouteStopOptions(Child $child): array
    {
        $route = $this->resolveRouteForMobileParentChild($child);
        $effectiveRouteId = (int) ($route?->id ?? $child->route_id ?? 0);
        $items = collect();

        if (Schema::hasTable('stops_pickup') && $effectiveRouteId > 0) {
            $items = StopPickup::query()
                ->where('route_id', $effectiveRouteId)
                ->where(function ($query) {
                    $query->where('deleted', 0)->orWhereNull('deleted');
                })
                ->orderBy('sequence_order')
                ->orderBy('id')
                ->get(['id', 'pickup_name', 'stop_name', 'sequence_order', 'latitude', 'longitude'])
                ->map(function (StopPickup $stop, int $index) {
                    $pickupName = $this->firstNonEmptyString($stop->pickup_name, $stop->stop_name);
                    $stopName = $this->firstNonEmptyString($stop->stop_name, $stop->pickup_name);

                    return [
                        'id' => (int) $stop->id,
                        'sequenceOrder' => (int) ($stop->sequence_order ?? $index + 1),
                        'pickupName' => $pickupName,
                        'stopName' => $stopName,
                        'label' => $pickupName,
                        'latitude' => $stop->latitude,
                        'longitude' => $stop->longitude,
                    ];
                })
                ->filter(fn (array $stop) => $stop['pickupName'] !== '' || $stop['stopName'] !== '')
                ->pipe(fn ($stops) => $this->dedupeMobileRouteStopOptions($stops));
        }

        if ($items->isNotEmpty()) {
            return $items->all();
        }

        $routeJson = is_array($route?->route_json ?? null) ? $route->route_json : [];
        $pickupPoints = is_array($routeJson['pickup_points'] ?? null) ? array_values($routeJson['pickup_points']) : [];
        $fallbackDropName = $this->firstNonEmptyString(
            data_get($routeJson, 'end_point.name'),
            data_get($routeJson, 'end_point.stop_name'),
            data_get($routeJson, 'end_point.pickup_name'),
            $child->school?->school_name ?? null,
            $route?->name ?? null
        );

        return collect($pickupPoints)
            ->filter(fn ($point) => is_array($point))
            ->map(function (array $point, int $index) use ($fallbackDropName) {
                $pickupName = $this->firstNonEmptyString(
                    data_get($point, 'name'),
                    data_get($point, 'pickup_name'),
                    data_get($point, 'stop_name'),
                    data_get($point, 'address')
                );
                $stopName = $this->firstNonEmptyString(
                    data_get($point, 'stop_name'),
                    $fallbackDropName,
                    $pickupName
                );

                return [
                    'id' => (int) ($point['id'] ?? $index + 1),
                    'sequenceOrder' => (int) ($point['sequence_order'] ?? $point['sequenceOrder'] ?? $index + 1),
                    'pickupName' => $pickupName,
                    'stopName' => $stopName,
                    'label' => $pickupName,
                    'latitude' => data_get($point, 'lat', data_get($point, 'latitude')),
                    'longitude' => data_get($point, 'lng', data_get($point, 'longitude')),
                ];
            })
            ->filter(fn (array $stop) => $stop['pickupName'] !== '' || $stop['stopName'] !== '')
            ->pipe(fn ($stops) => $this->dedupeMobileRouteStopOptions($stops))
            ->all();
    }

    private function dedupeMobileRouteStopOptions($items): Collection
    {
        return collect($items)
            ->groupBy(function (array $stop) {
                return strtolower(trim((string) ($stop['pickupName'] ?? ''))) . '|' .
                    strtolower(trim((string) ($stop['stopName'] ?? '')));
            })
            ->map(function ($groupedStops) {
                return collect($groupedStops)
                    ->sortBy([
                        ['sequenceOrder', 'asc'],
                        ['id', 'asc'],
                    ])
                    ->first();
            })
            ->values();
    }

    private function resolveMobileRouteEndpointLabel($route): string
    {
        $routeJson = $route?->route_json ?? null;
        if (is_string($routeJson)) {
            $decoded = json_decode($routeJson, true);
            $routeJson = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($routeJson)) {
            $routeJson = [];
        }

        $endPoint = is_array($routeJson['end_point'] ?? null) ? $routeJson['end_point'] : [];

        return $this->firstNonEmptyString(
            data_get($endPoint, 'name'),
            data_get($endPoint, 'stop_name'),
            data_get($endPoint, 'pickup_name'),
            data_get($endPoint, 'address'),
            $route?->name ?? null,
            'School'
        );
    }

    private function decodeMobileTripStops($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function findMobileTripChildStop(array $stops, int $childId, string $tripType): ?array
    {
        $preferredType = $tripType === 'afternoon' ? 'dropoff' : 'pickup';

        foreach ($stops as $stop) {
            if (! is_array($stop)) {
                continue;
            }

            if ((int) ($stop['childId'] ?? $stop['child_id'] ?? 0) === $childId
                && strtolower((string) ($stop['type'] ?? '')) === $preferredType) {
                return $stop;
            }
        }

        foreach ($stops as $stop) {
            if (is_array($stop) && (int) ($stop['childId'] ?? $stop['child_id'] ?? 0) === $childId) {
                return $stop;
            }
        }

        return null;
    }

    private function mapMobileTripTimelineStops(array $stops, int $childId, string $tripType): array
    {
        return collect($stops)
            ->filter(fn ($stop) => is_array($stop))
            ->map(function (array $stop, int $index) use ($childId, $tripType) {
                $type = strtolower((string) ($stop['type'] ?? 'stop'));
                $stopChildId = (int) ($stop['childId'] ?? $stop['child_id'] ?? 0);
                $label = $this->firstNonEmptyString(
                    data_get($stop, 'stopLabel'),
                    data_get($stop, 'pickupName'),
                    data_get($stop, 'stopName'),
                    data_get($stop, 'name'),
                    'Stop '.($index + 1)
                );

                return [
                    'label' => $label,
                    'type' => $type,
                    'status' => $this->firstNonEmptyString(data_get($stop, 'status'), 'pending'),
                    'completedAt' => $this->mobileIsoDate(data_get($stop, 'completedAt') ?? data_get($stop, 'completed_at')),
                    'sequenceOrder' => (int) ($stop['sequenceOrder'] ?? $stop['sequence_order'] ?? $index + 1),
                    'childName' => (string) ($stop['name'] ?? ''),
                    'isCurrentChild' => $stopChildId > 0 && $stopChildId === $childId,
                    'role' => $this->resolveMobileTimelineStopRole($type, $tripType, $stopChildId === $childId),
                ];
            })
            ->sortBy('sequenceOrder')
            ->values()
            ->all();
    }

    private function resolveMobileTimelineStopRole(string $type, string $tripType, bool $isCurrentChild): string
    {
        if ($type === 'pickup') {
            return $tripType === 'morning'
                ? ($isCurrentChild ? 'Child pickup' : 'Pickup')
                : 'School start';
        }

        if ($type === 'dropoff') {
            return $tripType === 'afternoon'
                ? ($isCurrentChild ? 'Child drop' : 'Drop')
                : 'School end';
        }

        return $tripType === 'afternoon' ? 'Route start' : 'Route stop';
    }

    private function mobileIsoDate($value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toIso8601String();
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function resolveMobileParentChildTripActive(int $routeId): bool
    {
        if ($routeId <= 0 || ! Schema::hasTable('trips') || ! Schema::hasColumn('trips', 'status')) {
            return false;
        }

        $routeColumn = null;
        if (Schema::hasColumn('trips', 'routeId')) {
            $routeColumn = 'routeId';
        } elseif (Schema::hasColumn('trips', 'route_id')) {
            $routeColumn = 'route_id';
        }

        if (! $routeColumn) {
            return false;
        }

        return DB::table('trips')
            ->where($routeColumn, $routeId)
            ->where('status', 'running')
            ->exists();
    }

    private function resolveMobileParentChildPickupPin(Child $child): string
    {
        if (! $this->isMobileParentChildPickupPending($child)) {
            return '';
        }

        if (Schema::hasTable('child_trip_pins')) {
            $activePin = DB::table('child_trip_pins')
                ->where('child_id', (int) $child->id)
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->orderByDesc('id')
                ->value('pin');

            if ($activePin !== null && trim((string) $activePin) !== '') {
                return (string) $activePin;
            }
        }

        return '';
    }

    private function isMobileParentChildPickupPending(Child $child): bool
    {
        if (! Schema::hasTable('trips')) {
            return true;
        }

        $rows = DB::table('trips')
            ->where('status', 'running')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['stops']);

        foreach ($rows as $row) {
            $stops = $row->stops;
            if (is_string($stops)) {
                $decoded = json_decode($stops, true);
                $stops = is_array($decoded) ? $decoded : [];
            }

            if (! is_array($stops)) {
                continue;
            }

            $childExistsInTrip = false;

            foreach ($stops as $stop) {
                if (! is_array($stop)) {
                    continue;
                }

                if ((int) ($stop['childId'] ?? 0) !== (int) $child->id) {
                    continue;
                }

                $childExistsInTrip = true;
                $type = strtolower(trim((string) ($stop['type'] ?? '')));
                $status = strtolower(trim((string) ($stop['status'] ?? '')));
                $skipped = ($stop['skipped'] ?? false) === true;

                if ($type === 'pickup' && $status === 'pending' && ! $skipped) {
                    return true;
                }
            }

            if ($childExistsInTrip) {
                return false;
            }
        }

        return false;
    }

    private function resolveRouteForMobileParentChild(Child $child): ?Route
    {
        $route = $child->relationLoaded('route')
            ? $child->route
            : $child->route()->with(['driver', 'vehicle'])->first();

        if ($route) {
            $route->loadMissing(['driver', 'vehicle']);
            return $route;
        }

        $candidateRouteIds = collect();
        foreach (['pickup_name', 'stop_name'] as $field) {
            $stopPickupId = $child->{$field} ?? null;
            if (! is_numeric($stopPickupId) || (int) $stopPickupId <= 0) {
                continue;
            }

            $routeId = (int) StopPickup::query()
                ->where('id', (int) $stopPickupId)
                ->where(function ($query) {
                    $query->where('deleted', 0)->orWhereNull('deleted');
                })
                ->value('route_id');

            if ($routeId > 0) {
                $candidateRouteIds->push($routeId);
            }
        }

        $candidateRouteIds = $candidateRouteIds
            ->map(fn ($routeId) => (int) $routeId)
            ->filter(fn ($routeId) => $routeId > 0)
            ->unique()
            ->values();

        if ($candidateRouteIds->count() !== 1) {
            return null;
        }

        return Route::query()
            ->with(['driver', 'vehicle'])
            ->where('id', (int) $candidateRouteIds->first())
            ->where(function ($query) {
                $query->where('deleted', 0)->orWhereNull('deleted');
            })
            ->first();
    }

    private function mapMobileParentRouteResponse($route, array $routeJson): ?array
    {
        if (! $route && $routeJson === []) {
            return null;
        }

        $startPoint = is_array($routeJson['start_point'] ?? null) ? $routeJson['start_point'] : null;
        $endPoint = is_array($routeJson['end_point'] ?? null) ? $routeJson['end_point'] : null;
        $pickupPoints = $this->dedupeMobileRoutePointList(
            is_array($routeJson['pickup_points'] ?? null) ? array_values($routeJson['pickup_points']) : []
        );
        $stops = $this->dedupeMobileRoutePointList(
            is_array($routeJson['stops'] ?? null) ? array_values($routeJson['stops']) : []
        );
        $geojson = is_array($routeJson['geojson'] ?? null) ? $routeJson['geojson'] : null;

        return [
            'id' => (int) ($route?->id ?? 0),
            'name' => (string) ($route?->name ?? ''),
            'startPoint' => $startPoint,
            'pickupPoints' => $pickupPoints,
            'endPoint' => $endPoint,
            'stops' => $stops,
            'geojson' => $geojson,
            'driverId' => (int) ($route?->driver_id ?? 0),
            'driverName' => (string) ($route?->driver?->driver_name ?? ''),
            'vehicleId' => (int) ($route?->bus_id ?? 0),
            'vehicleNumber' => (string) ($route?->vehicle?->vehicle_number ?? ''),
        ];
    }

    private function dedupeMobileRoutePointList(array $points): array
    {
        return collect($points)
            ->filter(fn ($point) => is_array($point))
            ->groupBy(function (array $point) {
                return strtolower(trim((string) (
                    data_get($point, 'name')
                    ?? data_get($point, 'address')
                    ?? data_get($point, 'pickup_name')
                    ?? data_get($point, 'stop_name')
                    ?? ''
                )));
            })
            ->map(function ($groupedPoints) {
                return collect($groupedPoints)
                    ->sortBy(function ($point) {
                        return (int) (data_get($point, 'sequence') ?? data_get($point, 'sequence_order') ?? data_get($point, 'sequenceOrder') ?? 0);
                    })
                    ->first();
            })
            ->values()
            ->all();
    }

    private function resolveManagedEmergencyContactsForUser(User $user): array
    {
        $parent = $this->resolveMobileParentProfile((int) $user->id, (string) $user->email);
        $children = $parent?->relationLoaded('children')
            ? $parent->children
            : ($parent?->children()->with(['school', 'route.driver'])->get() ?? collect());

        if ($children instanceof Collection && method_exists($children, 'loadMissing')) {
            $children = $children->loadMissing(['school', 'route.driver']);
        }

        $primaryChild = collect($children)->first(function ($child) {
            return (int) ($child->school_id ?? 0) > 0 || (int) ($child->route_id ?? 0) > 0;
        }) ?: collect($children)->first();

        $legacy = null;
        if (
            Schema::hasTable('emergency_contacts') &&
            Schema::hasColumn('emergency_contacts', 'user_id') &&
            Schema::hasColumn('emergency_contacts', 'school_contact') &&
            Schema::hasColumn('emergency_contacts', 'transport_contact')
        ) {
            $legacy = DB::table('emergency_contacts')
                ->select([
                    'school_contact as schoolContact',
                    'transport_contact as transportContact',
                    DB::raw(Schema::hasColumn('emergency_contacts', 'notes') ? 'notes' : 'NULL as notes'),
                ])
                ->where('user_id', (int) $user->id)
                ->first();
        }

        $schoolPhone = trim((string) ($primaryChild?->school?->phone ?? ''));
        $transportPhone = trim((string) ($primaryChild?->route?->driver?->emergency_phone ?? $primaryChild?->route?->driver?->driver_phone ?? ''));

        return [
            'schoolContact' => $this->firstNonEmptyString(
                $schoolPhone,
                $legacy->schoolContact ?? null,
                $transportPhone
            ),
            'transportContact' => $this->firstNonEmptyString(
                $transportPhone,
                $legacy->transportContact ?? null,
                $schoolPhone
            ),
            'notes' => $this->firstNonEmptyString(
                $legacy->notes ?? null,
                'Emergency contacts are managed by your school or admin team.'
            ),
            'schoolName' => $this->firstNonEmptyString(
                $primaryChild?->school?->school_name ?? null
            ),
            'transportName' => $this->firstNonEmptyString(
                $primaryChild?->route?->driver?->driver_name ?? null,
                'Transport Coordinator'
            ),
            'childName' => $this->firstNonEmptyString(
                $primaryChild?->child_name ?? null,
                $primaryChild?->name ?? null
            ),
            'editable' => false,
            'managedBy' => 'school_admin',
            'source' => ($schoolPhone !== '' || $transportPhone !== '')
                ? 'shared_school_records'
                : 'legacy_mobile_contacts',
        ];
    }

    private function storeMobileParentProfileImage(Request $request, int $userId, string $imagePayload, ?string $fileNameHint = null): string
    {
        $payload = trim($imagePayload);
        if ($payload === '') {
            return '';
        }

        if (! preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.+)$/', $payload, $matches)) {
            throw new \InvalidArgumentException('Invalid image payload');
        }

        $mimeType = strtolower((string) ($matches[1] ?? ''));
        $base64Data = (string) ($matches[2] ?? '');
        $extension = match ($mimeType) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => throw new \InvalidArgumentException('Unsupported image type'),
        };

        $safeHint = preg_replace('/[^a-zA-Z0-9._-]+/', '_', trim((string) $fileNameHint)) ?: '';
        $safeHint = substr($safeHint, 0, 60);
        $fileName = 'parent_' . $userId . '_' . time() . ($safeHint !== '' ? '_' . $safeHint : '') . '.' . $extension;
        $directory = public_path('uploads/profile_pictures');

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $decoded = base64_decode($base64Data, true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid image data');
        }

        $written = file_put_contents($directory . DIRECTORY_SEPARATOR . $fileName, $decoded);
        if ($written === false) {
            throw new \RuntimeException('Unable to store profile image');
        }

        return asset('uploads/profile_pictures/' . $fileName);
    }

    private function firstNonEmptyString(...$values): string
    {
        foreach ($values as $value) {
            $normalized = trim((string) $value);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return '';
    }

    private function mobileAbsoluteUrl(Request $request, ?string $value): string
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return '';
        }

        if (
            str_starts_with($normalized, 'http://') ||
            str_starts_with($normalized, 'https://') ||
            str_starts_with($normalized, 'data:')
        ) {
            return preg_replace('#/public/storage/#', '/storage/', $normalized) ?: $normalized;
        }

        $normalized = ltrim($normalized, '/');

        if (str_starts_with($normalized, 'public/storage/')) {
            $normalized = substr($normalized, strlen('public/'));
        }

        if (
            str_starts_with($normalized, 'profile_pictures/') ||
            str_starts_with($normalized, 'photos/')
        ) {
            $normalized = 'storage/' . $normalized;
        }

        return asset($normalized);
    }
}
