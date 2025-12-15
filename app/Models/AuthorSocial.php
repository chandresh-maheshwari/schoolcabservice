<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AuthorSocial extends Model
{
    use HasFactory;
    protected $table = 'author_socials';

    protected $fillable = [
        'name',
        'social_link',
        'social_icon',
        'status',
    ];

    public static function getAuthorSocialData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('author_socials')
        ->where('author_socials.deleted', 0)

            ->select('id', 'name', 'social_link', 'social_icon', 'status')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where('name', 'like', '%' . $searchValue . '%');
            })
            ->orderBy($columnName, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        return $query;
    }

    public static function getAuthorSocialDataTotal($searchValue)
    {
        $query = DB::table('author_socials')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where('name', 'like', '%' . $searchValue . '%');
            })
            ->where('deleted', 0)
            ->count();

        return $query;
    }
} 