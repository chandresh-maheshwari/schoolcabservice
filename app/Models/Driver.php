<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Driver extends Model
{
    use HasFactory;
    protected $table = 'drivers';

    protected $fillable = [
        'user_id',
        'driver_name',
        'driver_phone',
        'driver_image',
        'emergency_phone',
        'license_no',
        'license_expiry_date',
        'license_image',
        'adher_no',
        'adher_card_iamge',
        'vehicle_id',
        'experience_years',
        'status',
        'is_assigned',
        'joining_date',
    ];

    // protected $casts = [
    //     'status'              => 'integer',
    //     'is_assigned'         => 'integer',
    //     'license_expiry_date' => 'date',
    //     'joining_date'        => 'date',
    // ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public static function getDriverData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc'])
            ? $columnSortOrder
            : 'asc';

        $query = DB::table('drivers')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'drivers.vehicle_id')
            ->where('drivers.deleted', 0)
            ->select(
                'drivers.id',
                'drivers.driver_name',
                'drivers.driver_phone',
                'drivers.driver_image',
                'drivers.emergency_phone',
                'drivers.license_no',
                'drivers.license_expiry_date',
                'drivers.license_image',
                'drivers.adher_no',
                'drivers.adher_card_iamge',
                'drivers.experience_years',
                'drivers.status',
                'drivers.is_assigned',
                'drivers.joining_date',
                'vehicles.vehicle_number',
                'vehicles.vehicle_type_id'  
            )
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('drivers.driver_name', 'like', '%' . $searchValue . '%')
                        ->orWhere('drivers.driver_phone', 'like', '%' . $searchValue . '%')
                        ->orWhere('drivers.license_no', 'like', '%' . $searchValue . '%')
                        ->orWhere('drivers.adher_no', 'like', '%' . $searchValue . '%')
                        ->orWhere('vehicles.vehicle_number', 'like', '%' . $searchValue . '%');
                });
            })
            ->orderBy($columnName, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        return $query;
    }

    public static function getDriverDataTotal($searchValue)
    {
        $query = DB::table('drivers')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'drivers.vehicle_id')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('drivers.driver_name', 'like', '%' . $searchValue . '%')
                        ->orWhere('drivers.driver_phone', 'like', '%' . $searchValue . '%')
                        ->orWhere('drivers.license_no', 'like', '%' . $searchValue . '%')
                        ->orWhere('drivers.adher_no', 'like', '%' . $searchValue . '%')
                        ->orWhere('vehicles.vehicle_number', 'like', '%' . $searchValue . '%');
                });
            })
            ->where('drivers.deleted', 0)
            ->count();

        return $query;
    }

}
