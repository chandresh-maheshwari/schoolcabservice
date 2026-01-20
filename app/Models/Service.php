<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $table = 'service';

    protected $fillable = [
        'icon',
        'name',
        'description',
        'status',
        'deleted',
    ];

    protected $attributes = [
        'status'  => 0,
        'deleted' => 0,
    ];

    public static function getServiceData(
        $searchValue,
        $columnName,
        $columnSortOrder,
        $row,
        $rowperpage
    ) {
        // Secure sort order
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc'])
            ? $columnSortOrder
            : 'asc';

        // Allowed columns
        $allowedColumns = [
            'id',
            'icon',
            'name',
            'description',
            'status',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : 'id';

        // Base query (exclude soft deleted)
        $query = self::where('deleted', 0);

        // Search filter
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('icon', 'like', "%$searchValue%")
                    ->orWhere('name', 'like', "%$searchValue%")
                    ->orWhere('description', 'like', "%$searchValue%");
            });
        }

        // Pagination + Sorting
        return $query
            ->orderBy($columnName, $columnSortOrder)
            ->skip((int) $row)
            ->take((int) $rowperpage)
            ->get();
    }

    /**
     * Datatable total count
     */
    public static function getServiceDataTotal($searchValue)
    {
        $query = self::where('deleted', 0);

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('icon', 'like', "%$searchValue%")
                    ->orWhere('name', 'like', "%$searchValue%")
                    ->orWhere('description', 'like', "%$searchValue%");
            });
        }

        return $query->count();
    }
}
