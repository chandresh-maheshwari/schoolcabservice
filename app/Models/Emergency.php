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
        'user_id',
        'driver_id',
        'vehicle_id',
        'reported_by',
        'emergency_type',
        'description',
        'contact_number',
        'status',
        'additional_comment',
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
      $query = Emergency::with(['driver', 'vehicle'])->where('deleted', 0);


        // Search filter
       if (!empty($searchValue)) {
    $query->where(function ($q) use ($searchValue) {

        // emergency table fields
        $q->where('reported_by', 'like', "%$searchValue%")
          ->orWhere('emergency_type', 'like', "%$searchValue%")
          ->orWhere('description', 'like', "%$searchValue%")
          ->orWhere('contact_number', 'like', "%$searchValue%")
          ->orWhere('status', 'like', "%$searchValue%");

        // driver name search
        $q->orWhereHas('driver', function ($driverQuery) use ($searchValue) {
            $driverQuery->where('driver_name', 'like', "%$searchValue%");
        });

        // vehicle number search
        $q->orWhereHas('vehicle', function ($vehicleQuery) use ($searchValue) {
            $vehicleQuery->where('vehicle_number', 'like', "%$searchValue%");
        });

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

        if (!empty($searchValue)) {
    $query->where(function ($q) use ($searchValue) {

        // emergency table fields
        $q->where('reported_by', 'like', "%$searchValue%")
          ->orWhere('emergency_type', 'like', "%$searchValue%")
          ->orWhere('description', 'like', "%$searchValue%")
          ->orWhere('contact_number', 'like', "%$searchValue%")
          ->orWhere('status', 'like', "%$searchValue%");

        // driver name search
        $q->orWhereHas('driver', function ($driverQuery) use ($searchValue) {
            $driverQuery->where('driver_name', 'like', "%$searchValue%");
        });

        // vehicle number search
        $q->orWhereHas('vehicle', function ($vehicleQuery) use ($searchValue) {
            $vehicleQuery->where('vehicle_number', 'like', "%$searchValue%");
        });

    });
}

        return $query->count();
    }
}
