<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MagazineCategory extends Model
{
    use HasFactory;
    protected $table = 'magazine_categories';

    protected $fillable = [
        'name',
    ];

    public static function getCategoryData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('magazine_categories')
            ->where('magazine_categories.deleted', 0)
            ->select('id', 'name')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where('name', 'like', '%' . $searchValue . '%');
            })
            ->orderBy($columnName, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        return $query;
    }

    public static function getCategoryDataTotal($searchValue)
    {
        $query = DB::table('magazine_categories')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where('name', 'like', '%' . $searchValue . '%');
            })
            ->where('deleted', 0)
            ->count();

        return $query;
    }
} 