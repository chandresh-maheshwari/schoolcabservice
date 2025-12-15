<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CapabilitiesModel extends Model
{
    use HasFactory;
    protected $table = 'capabilities';

    protected $fillable = [
        'title',
        'description',
        'capability_icon',
        'progress_label',
        'progress_indicator',
        'image',
        'status',
        'deleted',
    ];

    public static function getCapabilityData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('capabilities')
            ->where('capabilities.deleted', 0)
            // ->select('id', 'title', 'description as description')
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

    public static function getCapabilityDataTotal($searchValue)
    {
        $query = DB::table('capabilities')
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
