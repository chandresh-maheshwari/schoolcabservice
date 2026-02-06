<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{

    use HasFactory;

    // protected $connection = 'mongodb';
    protected $table = 'ratings';
    protected $fillable   = [
        'driver_id',
        'vehicle_id',
        'rating',
        'comments',
        'deleted',
    ];

    protected $attributes = [
        'deleted' => 0,
    ];

       public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public static function getRatingData(
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
            'rating',
            'comments',
            'deleted',
            'created_at',
            'updated_at',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : 'id';

        // Base query (exclude deleted)
        $query = Rating::with(['driver', 'vehicle'])->where('deleted', 0);

        // Search filter
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('driver_id', 'like', "%$searchValue%")
                    ->orWhere('vehicle_id', 'like', "%$searchValue%")
                    ->orWhere('rating', 'like', "%$searchValue%")
                    ->orWhere('comments', 'like', "%$searchValue%");
            });
        }

        // Pagination + Sorting
        return $query
            ->orderBy($columnName, $columnSortOrder)
            ->skip((int) $row)
            ->take((int) $rowperpage)
            ->get();
    }

    public static function getRatingDataTotal($searchValue)
    {
        $query = self::where('deleted', 0);

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('driver_id', 'like', "%$searchValue%")
                    ->orWhere('vehicle_id', 'like', "%$searchValue%")
                    ->orWhere('rating', 'like', "%$searchValue%")
                    ->orWhere('comments', 'like', "%$searchValue%");
            });
        }

        return $query->count();
    }

}
