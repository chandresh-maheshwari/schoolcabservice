<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdvanceCapabilitiesModel extends Model
{
    use HasFactory;
    protected $table = 'advance_capabilities';

    protected $fillable = [
        'title',
        'description',
        'advance_capability_icon',
        'feature_benifit_1',
        'feature_benifit_2',
        'feature_status_badge',
        'image',
        'status',
        'deleted',
    ];

    public static function getAdvanceCapabilityData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('advance_capabilities')
            ->where('advance_capabilities.deleted', 0)
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

    public static function getAdvanceCapabilityDataTotal($searchValue)
    {
        $query = DB::table('advance_capabilities')
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
