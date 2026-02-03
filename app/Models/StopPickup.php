<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StopPickup extends Model
{
     use HasFactory;

    // protected $connection = 'mongodb';
    protected $table = 'stops_pickup';

    protected $fillable = [
        'route_id',
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

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }

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
        'id',
        'route_id',
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
        : 'id';

    // Base query (exclude deleted)
   $query = self::where('stops_pickup.deleted', 0)
    ->with('route:id,name')
    ->leftJoin('routes', 'routes.id', '=', 'stops_pickup.route_id')
    ->select('stops_pickup.*');

    // Search filter
    if (! empty($searchValue)) {
        $query->where(function ($q) use ($searchValue) {
            $q->where('routes.name', 'like', "%$searchValue%")
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
  $query = self::where('stops_pickup.deleted', 0)
            ->leftJoin('routes', 'routes.id', '=', 'stops_pickup.route_id');

    if (! empty($searchValue)) {
        $query->where(function ($q) use ($searchValue) {
            $q->where('routes.name', 'like', "%$searchValue%")
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
