<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestimonialSection extends Model
{
    protected $table = 'testimonial_sections';

    protected $fillable = [
        'name',
        'designation',
        'tagline',
        'description',
        'profile_image',
        'rating',
        'status',
        'deleted',
    ];

     protected $attributes = [
        'status'  => 0,
        'deleted' => 0,
    ];

    public static function getTestimonialData(
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

    // Allowed columns (Testimonial fields)
    $allowedColumns = [
        'id',
        'name',
        'designation',
        'tagline',
        'description',
        'profile_image',
        'rating',
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
              ->orWhere('designation', 'like', "%$searchValue%")
              ->orWhere('tagline', 'like', "%$searchValue%")
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

public static function getTestimonialDataTotal($searchValue)
{
    $query = self::where('deleted', 0);

    if (!empty($searchValue)) {
        $query->where(function ($q) use ($searchValue) {
            $q->where('name', 'like', "%$searchValue%")
              ->orWhere('designation', 'like', "%$searchValue%")
              ->orWhere('tagline', 'like', "%$searchValue%")
              ->orWhere('description', 'like', "%$searchValue%");
        });
    }

    return $query->count();
}
}
