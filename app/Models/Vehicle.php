<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Vehicle extends Model
{
    use HasFactory;

    protected $table = 'vehicles';

    protected $fillable = [
        'vehicle_number',
        'vehicle_image',
        'vehicle_type_id',
        'seating_capacity',
        'rc_number',
        'rc_expiry_date',
        'rc_image',
        'insurance_number',
        'insurance_expiry_date',
        'insurance_image',
        'is_assigned',
        'status',
    ];

    /**
     * Relation: Vehicle belongs to VehicleType
     */
    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class, 'vehicle_type_id');
    }


public static function getVehicleData($searchValue,$columnName,$columnSortOrder,$draw,$row,$rowperpage
) {
    // secure sort order
    $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc'])
        ? $columnSortOrder
        : 'asc';

    $query = DB::table('vehicles')
        ->leftJoin('vehicle_types', 'vehicle_types.id', '=', 'vehicles.vehicle_type_id')
        ->where('vehicles.deleted', 0)   // agar deleted flag hai
        ->select(
            'vehicles.id',
            'vehicles.vehicle_number',
            'vehicles.vehicle_image',
            'vehicles.seating_capacity',
            'vehicles.rc_number',
            'vehicles.rc_expiry_date',
            'vehicles.rc_image',
            'vehicles.insurance_number',
            'vehicles.insurance_expiry_date',
            'vehicles.insurance_image',
            'vehicles.is_assigned',
            'vehicles.status',
            'vehicle_types.vehicle_type as vehicle_type_name'
        )
        ->when($searchValue, function ($query, $searchValue) {
            return $query->where(function ($q) use ($searchValue) {
                $q->where('vehicles.vehicle_number', 'like', '%' . $searchValue . '%')
                  ->orWhere('vehicles.rc_number', 'like', '%' . $searchValue . '%')
                  ->orWhere('vehicles.insurance_number', 'like', '%' . $searchValue . '%')
                  ->orWhere('vehicle_types.vehicle_type', 'like', '%' . $searchValue . '%');
            });
        })
        ->orderBy($columnName, $columnSortOrder)
        ->skip($row)
        ->take($rowperpage)
        ->get();

    return $query;
}
public static function getVehicleDataTotal($searchValue)
{
    $query = DB::table('vehicles')
        ->leftJoin('vehicle_types', 'vehicle_types.id', '=', 'vehicles.vehicle_type_id')
        ->when($searchValue, function ($query, $searchValue) {
            return $query->where(function ($q) use ($searchValue) {
                $q->where('vehicles.vehicle_number', 'like', '%' . $searchValue . '%')
                  ->orWhere('vehicles.rc_number', 'like', '%' . $searchValue . '%')
                  ->orWhere('vehicles.insurance_number', 'like', '%' . $searchValue . '%')
                  ->orWhere('vehicle_types.vehicle_type', 'like', '%' . $searchValue . '%');
            });
        })
        ->where('vehicles.deleted', 0)
        ->count();

    return $query;
}

}
