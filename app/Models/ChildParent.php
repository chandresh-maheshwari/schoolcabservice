<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class ChildParent extends Model
{

     use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'children_parent';

    protected $fillable = [
        'child_name',
        'gender',
        'date_of_birth',
        'class',
        'section',
        'father_name',
        'mother_name',
        'contact_number',
        'alternative_contact_number',
        'email',
        'address_1',
        'address_2',
        'city',
        'state',
        'pincode',
        'school_id',
        'pickup_id',
        'stop_id',
        'route_id',
        'status',
        'deleted',
    ];

      protected $attributes = [
        'status'  => 0,
        'deleted' => 0,
    ];

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
        '_id',
         'child_name',
        'gender',
        'date_of_birth',
        'class',
        'section',
        'father_name',
        'mother_name',
        'contact_number',
        'alternative_contact_number',
        'email',
        'address_1',
        'address_2',
        'city',
        'state',
        'pincode',
        'school_id',
        'pickup_id',
        'stop_id',
        'route_id',
        'status',
        'deleted',
        'created_at',
        'updated_at',
    ];

    $columnName = in_array($columnName, $allowedColumns)
        ? $columnName
        : '_id';

    // Base query (exclude deleted)
    $query = self::where(function ($q) {
        $q->where('deleted', 0)
          ->orWhereNull('deleted');
    });

    // Search filter
    if (!empty($searchValue)) {
        $query->where(function ($q) use ($searchValue) {
            $q->where('child_name', 'like', "%$searchValue%")
              ->orWhere('father_name', 'like', "%$searchValue%")
              ->orWhere('mother_name', 'like', "%$searchValue%")
              ->orWhere('contact_number', 'like', "%$searchValue%")
              ->orWhere('email', 'like', "%$searchValue%")
              ->orWhere('class', 'like', "%$searchValue%")
              ->orWhere('section', 'like', "%$searchValue%")
              ->orWhere('city', 'like', "%$searchValue%")
              ->orWhere('state', 'like', "%$searchValue%")
              ->orWhere('pincode', 'like', "%$searchValue%");
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

    if (!empty($searchValue)) {
        $query->where(function ($q) use ($searchValue) {
            $q->where('child_name', 'like', "%$searchValue%")
              ->orWhere('father_name', 'like', "%$searchValue%")
              ->orWhere('mother_name', 'like', "%$searchValue%")
              ->orWhere('contact_number', 'like', "%$searchValue%")
              ->orWhere('email', 'like', "%$searchValue%")
              ->orWhere('class', 'like', "%$searchValue%")
              ->orWhere('section', 'like', "%$searchValue%")
              ->orWhere('city', 'like', "%$searchValue%")
              ->orWhere('state', 'like', "%$searchValue%")
              ->orWhere('pincode', 'like', "%$searchValue%");
        });
    }

    return $query->count();
}

}
