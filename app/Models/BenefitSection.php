<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BenefitSection extends Model
{
    protected $table = 'benefit_section';

    protected $fillable = [
        'name',
        'short_des',
        'description',
        'image',
        'status',
        'deleted',
    ];

    protected $attributes = [
        'status'  => 0,
        'deleted' => 0,
    ];

    /**
 * Datatable list data
 */
public static function getBenefitData(
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

    // Allowed columns (Benefit fields)
    $allowedColumns = [
        'id',
        'name',
        'short_des',
        'description',
        'image',
        'status',
    ];

    $columnName = in_array($columnName, $allowedColumns)
        ? $columnName
        : 'id';

    // Base query (exclude deleted)
    $query = self::where('deleted', 0);

    // Search filter
    if (!empty($searchValue)) {
        $query->where(function ($q) use ($searchValue) {
            $q->where('name', 'like', "%$searchValue%")
              ->orWhere('short_des', 'like', "%$searchValue%")
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
public static function getBenefitDataTotal($searchValue)
{
    $query = self::where('deleted', 0);

    if (!empty($searchValue)) {
        $query->where(function ($q) use ($searchValue) {
            $q->where('name', 'like', "%$searchValue%")
              ->orWhere('short_des', 'like', "%$searchValue%")
              ->orWhere('description', 'like', "%$searchValue%");
        });
    }

    return $query->count();
}
}
