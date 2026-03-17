<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Child extends Model
{
    use HasFactory;

    // protected $connection = 'mongodb';
    protected $table = 'children';

    protected $fillable = [
        'user_id',
        'child_name',
        'parent_id',
        'school_id',
        'pickup_name',
        'stop_name',
        'route_id',
        'secret_pin',
        'gender',
        'date_of_birth',
        'image',
        'child_adhaar_card_image',
        'class',
        'section',
        'status',
        'deleted',
    ];

    protected $attributes = [
        'status'  => 0,
        'deleted' => 0,
    ];

    public function parent()
    {
        return $this->belongsTo(Parents::class, 'parent_id');
    }

    // School
    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    // Route
    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public static function getChildData(
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
            'child_name',
            'parent_id',
            'school_id',
            'pickup_name',
            'stop_name',
            'route_id',
            'gender',
            'date_of_birth',
            'class',
            'section',
            'status',
            'deleted',
            'created_at',
            'updated_at',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : 'id';

        // Base query (exclude deleted)
        $query = self::where(function ($q) {
            $q->where('deleted', 0)
              ->orWhereNull('deleted');
        });

        // Search filter
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('parent_name', 'like', "%$searchValue%")
                 ->orWhere('school_name', 'like', "%$searchValue%")
                 ->orWhere('name', 'like', "%$searchValue%")
                  ->orWhere('class', 'like', "%$searchValue%")
                  ->orWhere('section', 'like', "%$searchValue%");
            });
        }

        // Pagination + Sorting
        return $query
            ->orderBy($columnName, $columnSortOrder)
            ->skip((int) $row)
            ->take((int) $rowperpage)
            ->get();
    }

     public static function getChildDataTotal($searchValue)
    {
        $query = self::where(function ($q) {
            $q->where('deleted', 0)
              ->orWhereNull('deleted');
        });

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
               $q->where('parent_name', 'like', "%$searchValue%")
                 ->orWhere('school_name', 'like', "%$searchValue%")
                 ->orWhere('name', 'like', "%$searchValue%")
                  ->orWhere('class', 'like', "%$searchValue%")
                  ->orWhere('section', 'like', "%$searchValue%");
            });
        }

        return $query->count();
    }
}
