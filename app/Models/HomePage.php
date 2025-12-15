<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class HomePage extends Model
{
    use HasFactory;
    protected $table = 'home_pages';

    protected $fillable = [
        'title',
        'category_id',
        'image',
        'description',
        'template',
    ];

    public static function getHomePageData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('home_pages')
            ->join('cms_categories', 'home_pages.category_id', '=', 'cms_categories.id')
            ->where('home_pages.deleted', 0)
            ->select('home_pages.id', 'home_pages.title', 'cms_categories.name as category', 'home_pages.image', 'home_pages.description')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where('home_pages.title', 'like', '%' . $searchValue . '%');
            })
            ->orderBy($columnName, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        return $query;
    }

    public static function getHomePageDataTotal($searchValue)
    {
        $query = DB::table('home_pages')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where('title', 'like', '%' . $searchValue . '%');
            })
            ->where('deleted', 0)
            ->count();

        return $query;
    }
} 