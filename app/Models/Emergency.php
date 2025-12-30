<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Emergency extends Model
{

    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'emergency_incidents';

    protected $fillable = [
        'driver_name',
        'vehicle_number',
        'reported_by',
        'emergency_type',
        'description',
        'contact_number',
        'status',
        'deleted',
    ];

    protected $attributes = [
        'status'  => 0,
        'deleted' => 0,
    ];

    public static function getEmergencyData(
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
            'reported_by',
            'emergency_type',
            'description',
            'contact_number',
            'status',
            'deleted',
            'created_at',
            'updated_at',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : '_id';

        // Base query
      $query = self::where('deleted', 0);

        // Search filter
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('driver_name', 'like', "%$searchValue%")
                    ->orWhere('vehicle_number', 'like', "%$searchValue%")
                    ->orWhere('reported_by', 'like', "%$searchValue%")
                    ->orWhere('emergency_type', 'like', "%$searchValue%")
                    ->orWhere('contact_number', 'like', "%$searchValue%");
            });
        }

        // Pagination + Sorting
        return $query
            ->orderBy($columnName, $columnSortOrder)
            ->skip((int) $row)
            ->take((int) $rowperpage)
            ->get();
    }

    public static function getEmergencyDataTotal($searchValue)
    {
        $query = self::where('deleted', 0);

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('driver_name', 'like', "%$searchValue%")
                    ->orWhere('vehicle_number', 'like', "%$searchValue%")
                    ->orWhere('reported_by', 'like', "%$searchValue%")
                    ->orWhere('emergency_type', 'like', "%$searchValue%")
                    ->orWhere('contact_number', 'like', "%$searchValue%");
            });
        }

        return $query->count();
    }
}
