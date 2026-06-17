<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Vehicle extends Model
{
    use HasFactory;

    protected $table = 'vehicles';

    protected $fillable = [
        'user_id',
        'school_id',
        'driver_id',
        'vehicle_number',
        'vehicle_type_id',
        'vehicle_image',
        'vehicle_type',
        'seating_capacity',
        'rc_number',
        'rc_expiry_date',
        'rc_image',
        'insurance_number',
        'insurance_expiry_date',
        'insurance_image',
        'current_latitude',
        'current_longitude',
        'current_speed_kmh',
        'location_source',
        'location_recorded_at',
        'is_assigned',
        'status',
        'deleted',
    ];

    protected $attributes = [
        'status'  => 0,
        'deleted' => 0,
    ];

    protected $casts = [
        'driver_id'             => 'integer',
        'current_latitude'     => 'decimal:7',
        'current_longitude'    => 'decimal:7',
        'current_speed_kmh'    => 'decimal:2',
        'location_recorded_at' => 'datetime',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id', 'id');
    }

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class, 'vehicle_type_id', 'id');
    }
  public function routes()
    {
        return $this->hasMany(Route::class, 'bus_id', 'id');
    }

//    public static function getVehicleData(
//     $searchValue,
//     $columnName,
//     $columnSortOrder,
//     $draw,
//     $row,
//     $rowperpage
// ) {
//     $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc'])
//         ? $columnSortOrder
//         : 'asc';

//     $allowedColumns = [
//         '_id',
//         'vehicle_number',
//         'seating_capacity',
//         'rc_number',
//         'rc_expiry_date',
//         'insurance_number',
//         'insurance_expiry_date',
//         'is_assigned',
//         'status',
//     ];

//     $columnName = in_array($columnName, $allowedColumns)
//         ? $columnName
//         : '_id';

//     $query = self::where(function ($q) {
//         $q->where('deleted', 0)
//           ->orWhereNull('deleted');
//     })->with('vehicleType');

//     // ✅ SEARCH LOGIC (IMPORTANT)
//     if (!empty($searchValue)) {
//         $query->where(function ($q) use ($searchValue) {
//             $q->where('vehicle_number', 'like', "%$searchValue%")
//               ->orWhere('rc_number', 'like', "%$searchValue%")
//               ->orWhere('insurance_number', 'like', "%$searchValue%")
//               ->orWhere('seating_capacity', 'like', "%$searchValue%");
//         });
//     }

//     return $query
//         ->orderBy($columnName, $columnSortOrder)
//         ->skip((int) $row)
//         ->take((int) $rowperpage)
//         ->get();
// }


 public static function getVehicleData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

      $query = Vehicle::with('vehicleType')
    ->where('deleted', 0)
            // ->select('id', 'title', 'description', 'service_icon')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('vehicle_number', 'like', '%' . $searchValue . '%')
                    ->orWhere('rc_number', 'like', '%' . $searchValue . '%')
                    ->orWhere('insurance_number', 'like', '%' . $searchValue . '%')
                    ->orWhere('seating_capacity', 'like', '%' . $searchValue . '%')
                    ->orWhereHas('vehicleType', function ($vehicleTypeQuery) use ($searchValue) {
                        $vehicleTypeQuery->where('vehicle_type', 'like', '%' . $searchValue . '%');
                    });



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
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('vehicle_number', 'like', '%' . $searchValue . '%')
                    ->orWhere('rc_number', 'like', '%' . $searchValue . '%')
                    ->orWhere('insurance_number', 'like', '%' . $searchValue . '%')
                    ->orWhere('seating_capacity', 'like', '%' . $searchValue . '%')
                    ->orWhereExists(function ($vehicleTypeQuery) use ($searchValue) {
                        $vehicleTypeQuery->select(DB::raw(1))
                            ->from('vehicle_types')
                            ->whereColumn('vehicle_types.id', 'vehicles.vehicle_type_id')
                            ->where('vehicle_types.vehicle_type', 'like', '%' . $searchValue . '%');
                    });
                });
            })
            ->where('deleted', 0)
            ->count();

        return $query;
}
}
