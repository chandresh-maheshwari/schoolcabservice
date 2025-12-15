<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class HeroModel extends Model
{
    use HasFactory;
    protected $table = 'hero_section';

    protected $fillable = [
        'title',
        'image',
        'description',
        'button_title_1',
        'button_title_2',
        // Stat fields
        'stat_counter_1',
        'stat_title_1',
        'stat_icon_1',
        'stat_counter_2',
        'stat_title_2',
        'stat_icon_2',
        'stat_counter_3',
        'stat_title_3',
        'stat_icon_3',
        'status'
    ];

    public static function getHeroData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('hero_section')
            ->where('hero_section.deleted', 0)
            // ->select('id', 'title', 'description as description', 'image')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('title', 'like', '%' . $searchValue . '%')
                      ->orWhere('description', 'like', '%' . $searchValue . '%');
                });
            })
            ->orderBy($columnName, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        return $query;
    }

    public static function getHeroDataTotal($searchValue)
    {
        $query = DB::table('hero_section')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('title', 'like', '%' . $searchValue . '%')
                      ->orWhere('description', 'like', '%' . $searchValue . '%');
                });
            })
            ->where('deleted', 0)
            ->count();

        return $query;
    }

}
