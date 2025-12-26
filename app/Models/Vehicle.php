<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $collection = 'vehicles';

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
        'deleted',
    ];

    protected $attributes = [
        'status'  => 0,
        'deleted' => 0,
    ];

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class, 'vehicle_type_id', '_id');
    }

    public static function getVehicleData(
        $searchValue,
        $columnName,
        $columnSortOrder,
        $draw,
        $row,
        $rowperpage
    ) {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc'])
            ? $columnSortOrder
            : 'asc';

        $allowedColumns = [
            '_id',
            'vehicle_number',
            'vehicle_image',
            'vehicle_type_id',
            'seating_capacity',
            'rc_number',
            'rc_expiry_date',
            'insurance_number',
            'insurance_expiry_date',
            'is_assigned',
            'status',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : '_id';

        $query = self::where(function ($q) {
            $q->where('deleted', 0)
                ->orWhereNull('deleted');
        })->with('vehicleType');
        return $query
            ->orderBy($columnName, $columnSortOrder)
            ->skip((int) $row)
            ->take((int) $rowperpage)
            ->get();
    }

    public static function getVehicleDataTotal($searchValue)
    {
        return self::where('deleted', 0)->count();
    }
}
