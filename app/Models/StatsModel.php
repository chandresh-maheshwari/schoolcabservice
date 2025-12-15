<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StatsModel extends Model
{
     use HasFactory;
    protected $table = 'stats';

    protected $fillable = [
        'stats_counter',
        'stat_icon',
        'stats_title',
        'image',
        'status',
        'deleted',
    ];

    public static function getStatsData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('stats')
            ->where('stats.deleted', 0)
            // ->select('id', 'stats_counter', 'stats_title')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('stats_counter', 'like', '%' . $searchValue . '%')
                      ->orWhere('stats_title', 'like', '%' . $searchValue . '%');
                });
            })
            ->orderBy($columnName, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        return $query;
    }

    public static function getStatsDataTotal($searchValue)
    {
        $query = DB::table('stats')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('stats_counter', 'like', '%' . $searchValue . '%')
                      ->orWhere('stats_title', 'like', '%' . $searchValue . '%');
                });
            })
            ->where('deleted', 0)
            ->count();

        return $query;
    }

}
