<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Rating extends Model
{

    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'ratings';
    protected $fillable   = [
        'driver_name',
        'vehicle_number',
        'rating',
        'comments',
        'deleted',
    ];

    protected $attributes = [
        'deleted' => 0,
    ];

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
            '_id',
            'driver_name',
            'vehicle_number',
            'rating',
            'comments',
            'deleted',
            'created_at',
            'updated_at',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : '_id';

        // Base query (exclude deleted)
        $query = self::where('deleted', 0);

        // Search filter
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('driver_name', 'like', "%$searchValue%")
                    ->orWhere('vehicle_number', 'like', "%$searchValue%")
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
                $q->where('driver_name', 'like', "%$searchValue%")
                    ->orWhere('vehicle_number', 'like', "%$searchValue%")
                    ->orWhere('rating', 'like', "%$searchValue%")
                    ->orWhere('comments', 'like', "%$searchValue%");
            });
        }

        return $query->count();
    }

}
