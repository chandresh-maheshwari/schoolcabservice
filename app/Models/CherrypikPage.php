<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CherrypikPage extends Model
{
    use HasFactory;

    protected $table = 'cherrypik_pages';

    protected $fillable = [
        'title',
        'slug',
        'template',
        'description',
        'image',
        'data',
        'status', 
        'inner_page_status',
        'hight', 
        'width', 
        'deleted',
    ];

    protected $casts = [
        'data' => 'array',
    ];
    public static function getPagesData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('cherrypik_pages')
        ->where('cherrypik_pages.deleted', 0)
            ->select('id', 'title', 'slug', 'template', 'description', 'image', 'status', 'inner_page_status')
          ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('title', 'like', '%' . $searchValue . '%')
                      ->orWhere('description', 'like', '%' . $searchValue . '%')
                      ->orWhere('template', 'like', '%' . $searchValue . '%');
                });
            })
            ->orderBy($columnName, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        return $query;
    }

    public static function getPagesDataTotal($searchValue)
    {
        $query = DB::table('cherrypik_pages')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('title', 'like', '%' . $searchValue . '%')
                      ->orWhere('description', 'like', '%' . $searchValue . '%')
                      ->orWhere('template', 'like', '%' . $searchValue . '%');
                });
            })
            ->where('deleted', 0)
            ->count();

        return $query;
    }
}


