<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class VehicleType extends Model
{
    use HasFactory;
    protected $table = 'vehicle_types';

    protected $fillable = [
        'vehicle_type',
        'status',
    ];

     public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public static function getVehicleTypeData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('vehicle_types')
            ->where('vehicle_types.deleted', 0)
            // ->select('id', 'title', 'description as description', 'image')
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
                });
            })
            ->where('deleted', 0)
            ->count();

        return $query;
    }
}
