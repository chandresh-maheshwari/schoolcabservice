<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SocialMediaModel extends Model
{
    use HasFactory;
    protected $table = 'socials_media';

    protected $fillable = [
        'name',
        'social_link',
        'social_icon',
        'status',
    ];

    public static function getSocialMediaData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('socials_media')
        ->where('socials_media.deleted', 0)

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

    public static function getSocialMediaDataTotal($searchValue)
    {
        $query = DB::table('socials_media')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where('name', 'like', '%' . $searchValue . '%');
            })
            ->where('deleted', 0)
            ->count();

        return $query;
    }
} 