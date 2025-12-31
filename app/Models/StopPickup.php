<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class StopPickup extends Model
{
     use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'stops_pickup';

    protected $fillable = [
        'name',
        'pickup_name',
        'stop_name',
        'latitude',
        'longitude',
        'sequence_order',
        'status',
        'deleted',
    ];

    protected $attributes = [
        'deleted' => 0,
    ];

    public static function getStopData(
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
        'name',
        'pickup_name',
        'stop_name',
        'latitude',
        'longitude',
        'sequence_order',
        'status',
        'deleted',
        'created_at',
    ];

    $columnName = in_array($columnName, $allowedColumns)
        ? $columnName
        : '_id';

    // Base query (exclude deleted)
    $query = self::where('deleted', 0);

    // Search filter
    if (! empty($searchValue)) {
        $query->where(function ($q) use ($searchValue) {
            $q->where('name', 'like', "%$searchValue%")
              ->orWhere('pickup_name', 'like', "%$searchValue%")
               ->orWhere('stop_name', 'like', "%$searchValue%")
              ->orWhere('latitude', 'like', "%$searchValue%")
              ->orWhere('longitude', 'like', "%$searchValue%")
              ->orWhere('sequence_order', 'like', "%$searchValue%");
        });
    }

    // Pagination + Sorting
    return $query
        ->orderBy($columnName, $columnSortOrder)
        ->skip((int) $row)
        ->take((int) $rowperpage)
        ->get();
}

public static function getStopDataTotal($searchValue)
{
    $query = self::where('deleted', 0);

    if (! empty($searchValue)) {
        $query->where(function ($q) use ($searchValue) {
            $q->where('name', 'like', "%$searchValue%")
              ->orWhere('pickup_name', 'like', "%$searchValue%")
               ->orWhere('stop_name', 'like', "%$searchValue%")
              ->orWhere('latitude', 'like', "%$searchValue%")
              ->orWhere('longitude', 'like', "%$searchValue%")
              ->orWhere('sequence_order', 'like', "%$searchValue%");
        });
    }

    return $query->count();
}
}
