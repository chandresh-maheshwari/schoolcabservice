<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class AboutSection extends Model
{
    use HasFactory;

    protected $collection = 'about_sections';

    protected $fillable = [
        'name',
        'title',
        'image',
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

     public static function getAboutSectionData(
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
            '_id',
            'title',
            'name',
            'description',
            'button_name',
            'button_link',
            'status',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : '_id';

        // Base query (exclude soft deleted)
        $query = self::where('deleted', 0);

        // Search filter
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('title', 'like', "%$searchValue%")
                    ->orWhere('name', 'like', "%$searchValue%")
                    ->orWhere('description', 'like', "%$searchValue%")
                    ->orWhere('button_name', 'like', "%$searchValue%")
                    ->orWhere('button_link', 'like', "%$searchValue%");
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
    public static function getAboutSectionDataTotal($searchValue)
    {
        $query = self::where('deleted', 0);

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('title', 'like', "%$searchValue%")
                    ->orWhere('name', 'like', "%$searchValue%")
                    ->orWhere('description', 'like', "%$searchValue%")
                    ->orWhere('button_name', 'like', "%$searchValue%")
                    ->orWhere('button_link', 'like', "%$searchValue%");
            });
        }

        return $query->count();
    }
}

