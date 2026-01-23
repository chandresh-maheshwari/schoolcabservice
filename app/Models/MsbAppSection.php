<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MsbAppSection extends Model
{
    protected $table = 'msb_app_section';

    protected $fillable = [
        'icon',
        'name',
        'description',
        'button_name',
        'button_link',
        'status',
        'deleted',
    ];

    protected $attributes = [
        'status'  => 0,
        'deleted' => 0,
    ];

    public static function getMsbAppSectionData(
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
        'button_name',
        'button_link',
        'status',
        'created_at',
    ];

    $columnName = in_array($columnName, $allowedColumns)
        ? $columnName
        : 'id';

    // Base query
    $query = self::where('deleted', 0);

    // 🔍 Search
    if (!empty($searchValue)) {
        $query->where(function ($q) use ($searchValue) {
            $q->where('name', 'like', "%$searchValue%")
              ->orWhere('description', 'like', "%$searchValue%")
              ->orWhere('button_name', 'like', "%$searchValue%");
        });
    }

    // Pagination + Sorting
    return $query
        ->orderBy($columnName, $columnSortOrder)
        ->skip((int) $row)
        ->take((int) $rowperpage)
        ->get();
}
public static function getMsbAppSectionDataTotal($searchValue)
{
    $query = self::where('deleted', 0);

    if (!empty($searchValue)) {
        $query->where(function ($q) use ($searchValue) {
            $q->where('name', 'like', "%$searchValue%")
              ->orWhere('description', 'like', "%$searchValue%")
              ->orWhere('button_name', 'like', "%$searchValue%");
        });
    }

    return $query->count();
}

}
