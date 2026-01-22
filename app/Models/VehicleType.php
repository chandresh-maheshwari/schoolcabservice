<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
// use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Model;


class VehicleType extends Model
{
    use HasFactory;

    protected $collection = 'vehicle_types';

    protected $fillable = [
        'vehicle_type',
        'status',
        'deleted',
    ];

    protected $attributes = [
        'status'  => 0,
        'deleted' => 0,
    ];
    /* ===============================
       Relationships
    =============================== */
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'vehicle_type_id', 'id');
    }

    /* ===============================
       DataTables: Get Data
    =============================== */
    // public static function getVehicleTypeData(
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

    //     // ✅ MongoDB-safe sortable columns
    //     $allowedColumns = ['vehicle_type', 'status', 'created_at'];
    //     $columnName     = in_array($columnName, $allowedColumns)
    //         ? $columnName
    //         : 'vehicle_type';

    //     $query = self::where('deleted', 0);

    //     if (! empty($searchValue)) {
    //         $query->where('vehicle_type', 'like', "%{$searchValue}%");
    //     }

    //     return $query
    //         ->orderBy($columnName, $columnSortOrder)
    //         ->skip((int) $row)
    //         ->take((int) $rowperpage)
    //         ->get();
    // }

    /* ===============================
       DataTables: Count Filtered
    =============================== */
    // public static function getVehicleTypeDataTotal($searchValue)
    // {
    //     $query = self::where('deleted', 0);

    //     if (! empty($searchValue)) {
    //         $query->where('vehicle_type', 'like', "%{$searchValue}%");
    //     }

    //     // dd($query);
    //     return $query->count();
    // }


     public static function getVehicleTypeData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('vehicle_types')
            ->where('vehicle_types.deleted', 0)
            // ->select('id', 'title', 'description', 'service_icon')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('vehicle_type', 'like', '%' . $searchValue . '%');

                });
            })
            ->orderBy($columnName, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        return $query;
    }

    public static function getVehicleTypeDataTotal($searchValue)
    {
        $query = DB::table('vehicle_types')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('vehicle_type', 'like', '%' . $searchValue . '%');
                    //   ->orWhere('description', 'like', '%' . $searchValue . '%');
                });
            })
            ->where('deleted', 0)
            ->count();

        return $query;
    }
}
