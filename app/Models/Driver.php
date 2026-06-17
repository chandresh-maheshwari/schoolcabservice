<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

// use MongoDB\Laravel\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;
    // protected $table = 'drivers';
    protected $table = 'drivers';

    protected $fillable = [
        'user_id',
        'school_id',
        'login_user_id',
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
        'deleted',
    ];
    // protected $attributes = [
    //     'status'  => 0,
    //     'deleted' => 0,
    // ];

    // protected $casts = [
    //     'status'              => 'integer',
    //     'is_assigned'         => 'integer',
    //     'license_expiry_date' => 'date',
    //     'joining_date'        => 'date',
    // ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function routes()
    {
        return $this->hasMany(Route::class, 'driver_id', 'id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'id');
    }

    // public static function getDriverData(
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
    //         'driver_name',
    //         'driver_phone',
    //         'emergency_phone',
    //         'license_no',
    //         'license_expiry_date',
    //         'experience_years',
    //         'status',
    //         'is_assigned',
    //         'joining_date',
    //     ];

    //     $columnName = in_array($columnName, $allowedColumns)
    //         ? $columnName
    //         : '_id';

    //     $query = self::where(function ($q) {
    //         $q->where('deleted', 0)
    //             ->orWhereNull('deleted');
    //     })->with('vehicle', 'user');

    //     // ✅ SEARCH LOGIC (MOST IMPORTANT)
    //     if (! empty($searchValue)) {
    //         $query->where(function ($q) use ($searchValue) {
    //             $q->where('driver_name', 'like', "%$searchValue%")
    //                 ->orWhere('driver_phone', 'like', "%$searchValue%")
    //                 ->orWhere('license_no', 'like', "%$searchValue%")
    //                 ->orWhere('adher_no', 'like', "%$searchValue%");
    //         });
    //     }

    //     return $query
    //         ->orderBy($columnName, $columnSortOrder)
    //         ->skip((int) $row)
    //         ->take((int) $rowperpage)
    //         ->get();
    // }

    public static function getDriverData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('drivers')
            ->where('drivers.deleted', 0)
            // ->select('id', 'title', 'description', 'service_icon')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('driver_name', 'like', "%$searchValue%")
                    ->orWhere('driver_phone', 'like', "%$searchValue%")
                    ->orWhere('license_no', 'like', "%$searchValue%")
                    ->orWhere('adher_no', 'like', "%$searchValue%");



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
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                     $q->where('driver_name', 'like', "%$searchValue%")
                    ->orWhere('driver_phone', 'like', "%$searchValue%")
                    ->orWhere('license_no', 'like', "%$searchValue%")
                    ->orWhere('adher_no', 'like', "%$searchValue%");
                });
            })
            ->where('deleted', 0)
            ->count();

        return $query;
    }

}
