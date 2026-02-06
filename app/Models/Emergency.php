<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Emergency extends Model
{

    use HasFactory;

    // protected $connection = 'mongodb';
    protected $table = 'emergency_incidents';

    protected $fillable = [
        'driver_id',
        'vehicle_id',
        'reported_by',
        'emergency_type',
        'description',
        'contact_number',
        'status',
        'deleted',
    ];

        public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }
    // protected $attributes = [
    //     'status'  => 0,
    //     'deleted' => 0,
    // ];

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
            'id',
            'driver_id',
            'vehicle_id',
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
            : 'id';

        // Base query
    //   $query = self::where('deleted', 0);
      $query = Emergency::with(['driver', 'vehicle']);


        // Search filter
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('driver_id', 'like', "%$searchValue%")
                    ->orWhere('vehicle_id', 'like', "%$searchValue%")
                    ->orWhere('reported_by', 'like', "%$searchValue%")
                    ->orWhere('emergency_type', 'like', "%$searchValue%")
                    ->orWhere('contact_number', 'like', "%$searchValue%");
            });
        }
        // dd($query->get());

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
                $q->where('driver_id', 'like', "%$searchValue%")
                    ->orWhere('vehicle_id', 'like', "%$searchValue%")
                    ->orWhere('reported_by', 'like', "%$searchValue%")
                    ->orWhere('emergency_type', 'like', "%$searchValue%")
                    ->orWhere('contact_number', 'like', "%$searchValue%");
            });
        }

        return $query->count();
    }
}
