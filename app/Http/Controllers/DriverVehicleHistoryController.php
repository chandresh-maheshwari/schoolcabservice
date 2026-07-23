<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DriverVehicleHistory;
use Illuminate\Support\Facades\Schema;

class DriverVehicleHistoryController extends Controller
{
    public function index()
    {
        return view('driver_history.index');
    }

    /**
     * Fetch driver vehicle history list for DataTable.
     * created by ns
     */
    public function driverHistoryList(Request $request)
    {
        $draw        = intval($request->input('sEcho'));
        $row         = intval($request->input('iDisplayStart'));
        $rowperpage  = intval($request->input('iDisplayLength'));
        $indexColumn = (int) $request->input('iSortCol_0', 0);
        $columnKey   = $request->input('mDataProp_' . $indexColumn, 'id');

        $sortableKeys = [
            'id',
            'school_name',
            'driver_name',
            'vehicle_number',
            'is_assigned',
        ];

        $columnKey = in_array($columnKey, $sortableKeys, true) ? $columnKey : 'id';

        $columnSortOrder = $request->input('sSortDir_0', 'asc');
        $searchValue     = $request->input('sSearch');

        $query = DriverVehicleHistory::query()
            ->with(['driver', 'vehicle'])
            ->where(function ($q) {
                $q->where('driver_vehicle_histories.deleted', 0)
                    ->orWhereNull('driver_vehicle_histories.deleted');
            });

        if ($columnKey === 'driver_name') {
            $query->leftJoin('drivers', 'driver_vehicle_histories.driver_id', '=', 'drivers.id');
        } elseif ($columnKey === 'vehicle_number') {
            $query->leftJoin('vehicles', 'driver_vehicle_histories.vehicle_id', '=', 'vehicles.id');
        } elseif ($columnKey === 'school_name') {
            $query->leftJoin('drivers', 'driver_vehicle_histories.driver_id', '=', 'drivers.id')
                ->leftJoin('vehicles', 'driver_vehicle_histories.vehicle_id', '=', 'vehicles.id')
                ->leftJoin('schools', function ($join) {
                    $join->where('schools.deleted', 0)
                        ->where(function ($schoolJoin) {
                            if (Schema::hasColumn('driver_vehicle_histories', 'school_id')) {
                                $schoolJoin->whereColumn('driver_vehicle_histories.school_id', 'schools.id');
                            }

                            if (Schema::hasColumn('drivers', 'school_id')) {
                                $method = Schema::hasColumn('driver_vehicle_histories', 'school_id') ? 'orWhereColumn' : 'whereColumn';
                                $schoolJoin->{$method}('drivers.school_id', 'schools.id');
                            }

                            if (Schema::hasColumn('vehicles', 'school_id')) {
                                $method = (
                                    Schema::hasColumn('driver_vehicle_histories', 'school_id')
                                    || Schema::hasColumn('drivers', 'school_id')
                                ) ? 'orWhereColumn' : 'whereColumn';
                                $schoolJoin->{$method}('vehicles.school_id', 'schools.id');
                            }

                            $schoolJoin->orWhereColumn('driver_vehicle_histories.user_id', 'schools.user_id')
                                ->orWhereColumn('drivers.user_id', 'schools.user_id')
                                ->orWhereColumn('vehicles.user_id', 'schools.user_id');
                        });
                });
        }

        $query->select('driver_vehicle_histories.*');
        $this->applyDriverHistoryOwnershipScope($query, $request);
        $totalRecords = (clone $query)->count();

        if (! empty($searchValue)) {
            $matchingSchoolReferences = $this->resolveSchoolSearchIds($searchValue);

            $query->where(function ($q) use ($searchValue, $matchingSchoolReferences) {
                $q->where('is_assigned', 'like', "%$searchValue%");

                // Keep relation-search grouped to avoid bypassing actor scope via top-level ORs.
                $q->orWhereHas('driver', function ($driverQuery) use ($searchValue) {
                    $driverQuery->where('driver_name', 'like', "%$searchValue%");
                })->orWhereHas('vehicle', function ($vehicleQuery) use ($searchValue) {
                    $vehicleQuery->where('vehicle_number', 'like', "%$searchValue%");
                });

                if (! empty($matchingSchoolReferences['school_ids']) && Schema::hasColumn('driver_vehicle_histories', 'school_id')) {
                    $q->orWhereIn('driver_vehicle_histories.school_id', $matchingSchoolReferences['school_ids']);
                }

                if (! empty($matchingSchoolReferences['user_ids'])) {
                    $q->orWhereIn('driver_vehicle_histories.user_id', $matchingSchoolReferences['user_ids'])
                        ->orWhereHas('driver', function ($driverQuery) use ($matchingSchoolReferences) {
                            $driverQuery->whereIn('user_id', $matchingSchoolReferences['user_ids']);
                        })
                        ->orWhereHas('vehicle', function ($vehicleQuery) use ($matchingSchoolReferences) {
                            $vehicleQuery->whereIn('user_id', $matchingSchoolReferences['user_ids']);
                        });
                }
            });
        }

        $totalRecordwithFilter = (clone $query)->count();

        $sortColumnMap = [
            'id' => 'driver_vehicle_histories.id',
            'is_assigned' => 'driver_vehicle_histories.is_assigned',
            'driver_name' => 'drivers.driver_name',
            'vehicle_number' => 'vehicles.vehicle_number',
            'school_name' => 'schools.school_name',
        ];

        $sortColumn = $sortColumnMap[$columnKey] ?? 'driver_vehicle_histories.id';

        $driverHistoryDetails = $query
            ->orderBy($sortColumn, in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'desc')
            ->skip($row)
            ->take($rowperpage)
            ->get();

        $data = [];
        $historySchoolIds = [];
        $historyOwnerUserIds = [];
        $driverIds = [];
        $vehicleIds = [];

        foreach ($driverHistoryDetails as $driverHistory) {
            $resolvedSchoolId = $this->resolveHistorySchoolId($driverHistory);
            if ($resolvedSchoolId) {
                $historySchoolIds[] = $resolvedSchoolId;
            }

            $ownerUserId = $this->resolveHistoryOwnerUserId($driverHistory);
            if ($ownerUserId) {
                $historyOwnerUserIds[] = $ownerUserId;
            }

            if (is_numeric($driverHistory->driver_id ?? null) && (int) $driverHistory->driver_id > 0) {
                $driverIds[] = (int) $driverHistory->driver_id;
            }

            if (is_numeric($driverHistory->vehicle_id ?? null) && (int) $driverHistory->vehicle_id > 0) {
                $vehicleIds[] = (int) $driverHistory->vehicle_id;
            }
        }

        $schoolNamesBySchoolId = $this->getSchoolNameMapForSchoolIds($historySchoolIds);
        $schoolNamesByUserId = $this->getSchoolNameMapForUserIds($historyOwnerUserIds);
        $schoolNamesByDriverId = $this->getSchoolNameMapForDriverIds($driverIds);
        $schoolNamesByVehicleId = $this->getSchoolNameMapForVehicleIds($vehicleIds);

        foreach ($driverHistoryDetails as $driverHistory) {
            $resolvedSchoolId = $this->resolveHistorySchoolId($driverHistory);
            $ownerUserId = $this->resolveHistoryOwnerUserId($driverHistory);

            $data[] = [
                'id'           => $driverHistory->id,
                'school_name'  => $schoolNamesBySchoolId[$resolvedSchoolId]
                    ?? $schoolNamesByUserId[$ownerUserId]
                    ?? $schoolNamesByDriverId[(int) ($driverHistory->driver_id ?? 0)]
                    ?? $schoolNamesByVehicleId[(int) ($driverHistory->vehicle_id ?? 0)]
                    ?? '-',
                'driver_name'    => optional($driverHistory->driver)->driver_name,
               'vehicle_number' => optional($driverHistory->vehicle)->vehicle_number,
                'is_assigned' => $driverHistory->is_assigned,
            ];
        }

        return response()->json([
            "draw"            => $draw,
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordwithFilter,
            "data"            => $data,
        ]);
    }

    /**
     * Delete driver vehicle history record.
     */
     public function destroy($schoolSlugOrId, $id = null)
    {
        $id = $this->normalizeRouteId($schoolSlugOrId, $id);

        $query = DriverVehicleHistory::query();
        $this->applyDriverHistoryOwnershipScope($query, request());
        $driverHistory = $query->findOrFail($id);

        $driverHistory->deleted = 1;
        $driverHistory->save();

        return response()->json(['success' => true, 'message' => 'Driver History deleted Successfully.']);
    }

    /**
     * Multi delete driver vehicle history records.
     */
    public function multiDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (!is_array($ids) || empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No IDs provided for deletion',
            ]);
        }

        $query = DriverVehicleHistory::whereIn('id', $ids);
        $this->applyDriverHistoryOwnershipScope($query, $request);
        $query->update(['deleted' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Selected driver history deleted successfully',
        ]);
    }

    private function applyDriverHistoryOwnershipScope($query, Request $request)
    {
        if (! $this->shouldRestrictToActorData($request)) {
            return $query;
        }

        $actorUserId = $this->resolveActorUserId($request);
        if (! $actorUserId) {
            return $query->whereRaw('1 = 0');
        }

        $schoolId = $this->resolveSchoolIdFromContext($request);

        return $query->where(function ($historyQuery) use ($actorUserId, $schoolId) {
            $historyQuery->where('driver_vehicle_histories.user_id', $actorUserId)
                ->orWhereHas('driver', function ($driverQuery) use ($actorUserId, $schoolId) {
                    $driverQuery->where('user_id', $actorUserId);

                    if ($schoolId && Schema::hasColumn('drivers', 'school_id')) {
                        $driverQuery->orWhere('school_id', $schoolId);
                    }
                })
                ->orWhereHas('vehicle', function ($vehicleQuery) use ($actorUserId, $schoolId) {
                    $vehicleQuery->where('user_id', $actorUserId);

                    if ($schoolId && Schema::hasColumn('vehicles', 'school_id')) {
                        $vehicleQuery->orWhere('school_id', $schoolId);
                    }
                });

            if ($schoolId && Schema::hasColumn('driver_vehicle_histories', 'school_id')) {
                $historyQuery->orWhere('driver_vehicle_histories.school_id', $schoolId);
            }
        });
    }

    private function resolveHistoryOwnerUserId($driverHistory): ?int
    {
        $candidateUserId = $driverHistory->user_id
            ?? optional($driverHistory->driver)->user_id
            ?? optional($driverHistory->vehicle)->user_id;

        return is_numeric($candidateUserId) ? (int) $candidateUserId : null;
    }

    private function resolveHistorySchoolId($driverHistory): ?int
    {
        $candidateSchoolId = null;

        if (Schema::hasColumn('driver_vehicle_histories', 'school_id')) {
            $candidateSchoolId = $driverHistory->school_id;
        }

        if (! is_numeric($candidateSchoolId) || (int) $candidateSchoolId <= 0) {
            $candidateSchoolId = optional($driverHistory->driver)->school_id;
        }

        if (! is_numeric($candidateSchoolId) || (int) $candidateSchoolId <= 0) {
            $candidateSchoolId = optional($driverHistory->vehicle)->school_id;
        }

        return is_numeric($candidateSchoolId) && (int) $candidateSchoolId > 0
            ? (int) $candidateSchoolId
            : null;
    }
}
