<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class DriverVehicleHistory extends Model
{
     use HasFactory;
    protected $collection = 'driver_vehicle_histories';

    protected $fillable = [
        'driver_name',
        'vehicle_number',
        'is_assigned',
        'deleted',

    ];

     protected $attributes = [
        'is_assigned' => 0,
         'deleted' => 0,
    ];

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
        '_id',
        'driver_name',
        'vehicle_number',
        'is_assigned',
        'deleted',
    ];

    $columnName = in_array($columnName, $allowedColumns)
        ? $columnName
        : '_id';

    // Base query (NO deleted condition)
    $query = self::where('deleted', 0);

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
