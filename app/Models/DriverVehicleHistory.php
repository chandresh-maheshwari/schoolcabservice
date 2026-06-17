<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Support\Facades\DB;
// use App\Models\DriverVehicleHistory;
use Illuminate\Database\Eloquent\Model;



class DriverVehicleHistory extends Model
{
     use HasFactory;
    protected $table = 'driver_vehicle_histories';

    protected $fillable = [
        'user_id',
        'school_id',
        'driver_id',
        'vehicle_id',
        'is_assigned',
        'deleted',

    ];

     protected $attributes = [
        'is_assigned' => 0,
         'deleted' => 0,
    ];

      public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }
    public static function getDriverVehicleHistoryData(
    $searchValue,
    $columnName,
    $columnSortOrder,
    $draw,
    $row,
    $rowperpage
) {
    // Secure sort order
    $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc'])
        ? $columnSortOrder
        : 'asc';

    // Allowed sortable columns
    $allowedColumns = [
        'id',
        'driver_name',
        'vehicle_number',
        'is_assigned',
        'deleted',
    ];

    $columnName = in_array($columnName, $allowedColumns)
        ? $columnName
        : 'id';

    // Base query (NO deleted condition)
    $query = self::with(['driver', 'vehicle'])->where('deleted', 0);

    // Search filter
    if (! empty($searchValue)) {
        $query->where(function ($q) use ($searchValue) {
            $q->where('driver_name', 'like', "%$searchValue%");
        });
    }

    // Pagination + Sorting
    return $query
        ->orderBy($columnName, $columnSortOrder)
        ->skip((int) $row)
        ->take((int) $rowperpage)
        ->get();
}

public static function getDriverVehicleHistoryDataTotal($searchValue)
{
    $query = self::where('deleted', 0);

    if (! empty($searchValue)) {
        $query->where(function ($q) use ($searchValue) {
            $q->where('driver_name', 'like', "%$searchValue%");
        });
    }

    return $query->count();
}

}
