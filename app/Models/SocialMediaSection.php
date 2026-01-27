<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SocialMediaSection extends Model
{
    protected $table = 'social_media';

    protected $fillable = [
        'social_name',
        'social_link',
        'social_icon',
        'status',


    ];

    protected $attributes = [
        'status'  => 0,
        'deleted' => 0,
    ];
    public static function getSocialMediaData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('social_media')
        ->where('social_media.deleted', 0)

            ->select('id', 'social_name', 'social_link', 'social_icon' ,'status')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where('social_name', 'like', '%' . $searchValue . '%');
            })
            ->orderBy($columnName, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        return $query;
    }

    public static function getSocialMediaDataTotal($searchValue)
    {
        $query = DB::table('social_media')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where('social_name', 'like', '%' . $searchValue . '%');
            })
            ->where('deleted', 0)
            ->count();

        return $query;
    }
}
