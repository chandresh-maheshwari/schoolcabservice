<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class HeaderModel extends Model
{
    use HasFactory;
    protected $table = 'header';

    protected $fillable = [
        'title',
        'link',
        'button_title',
        'button_link',
        'image',
        'status'
    ];  

    public static function getheaderData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('header')
            ->where('header.deleted', 0)
            // ->select('id', 'title', 'description as description', 'image')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('title', 'like', '%' . $searchValue . '%')
                      ->orWhere('link', 'like', '%' . $searchValue . '%')
                      ->orWhere('button_title', 'like', '%' . $searchValue . '%')
                      ->orWhere('button_link', 'like', '%' . $searchValue . '%');
                });
            })
            ->orderBy($columnName, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        return $query;
    }

    public static function getheaderDataTotal($searchValue)
    {
        $query = DB::table('header')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                      $q->where('title', 'like', '%' . $searchValue . '%')
                      ->orWhere('link', 'like', '%' . $searchValue . '%')
                      ->orWhere('button_title', 'like', '%' . $searchValue . '%')
                      ->orWhere('button_link', 'like', '%' . $searchValue . '%');
                });
            })
            ->where('deleted', 0)
            ->count();

        return $query;
    }

}
