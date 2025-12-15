<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CallToActionModel extends Model
{
    use HasFactory;
    protected $table = 'call_to_action';

    protected $fillable = [
        'badge_title',
        'badge_icon',
        'feature_1',
        'feature_2',
        'feature_3',
        'feature_4',
        'stat_icon_1',
        'stat_count_1',
        'stat_text_1',
        'stat_icon_2',
        'stat_count_2',
        'stat_text_2',
        'button_title',
        'button_link',
        'status',
        'deleted',
    ];

    public static function getCallToActionData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('call_to_action')
            ->where('call_to_action.deleted', 0)
            // ->select('id', 'title', 'description as description')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('feature_1', 'like', '%' . $searchValue . '%')
                      ->orWhere('feature_2', 'like', '%' . $searchValue . '%')
                      ->orWhere('feature_3', 'like', '%' . $searchValue . '%')
                      ->orWhere('feature_4', 'like', '%' . $searchValue . '%')
                      ->orWhere('button_title', 'like', '%' . $searchValue . '%');
                });
            })
            ->orderBy($columnName, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        return $query;
    }

    public static function getCallToActionDataTotal($searchValue)
    {
        $query = DB::table('call_to_action')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('feature_1', 'like', '%' . $searchValue . '%')
                      ->orWhere('feature_2', 'like', '%' . $searchValue . '%')
                      ->orWhere('feature_3', 'like', '%' . $searchValue . '%')
                      ->orWhere('feature_4', 'like', '%' . $searchValue . '%')
                      ->orWhere('button_title', 'like', '%' . $searchValue . '%');
                });
            })
            ->where('deleted', 0)
            ->count();

        return $query;
    }

}
