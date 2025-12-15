<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FeatureModel extends Model
{
    use HasFactory;
    protected $table = 'features';

    protected $fillable = [
        'title',
        'image',
        'description',
        'highlight_number_1',
        'hightlight_text_1',
        'highlight_icone_1',
        'highlight_number_2',
        'hightlight_text_2',
        'highlight_icone_2',
        'highlight_number_3',
        'hightlight_text_3',
        'highlight_icone_3',
        'status',
    ];

    public static function getFeatureData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('features')
            ->where('features.deleted', 0)
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

    public static function getFeatureDataTotal($searchValue)
    {
        $query = DB::table('features')
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
