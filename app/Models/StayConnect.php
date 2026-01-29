<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StayConnect extends Model
{
    protected $table = 'stay_connect';

    protected $fillable = [
        'name',
        'company',
        'email',
    ];

    public static function getStayConnect($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('stay_connect')
            ->select('id', 'name', 'company', 'email')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where('name', 'like', '%' . $searchValue . '%')
                             ->orWhere('company', 'like', '%' . $searchValue . '%')
                             ->orWhere('email', 'like', '%' . $searchValue . '%');
            })
            ->orderBy($columnName, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        return $query;
    }

    public static function getStayConnectTotal($searchValue)
    {
        $query = DB::table('stay_connect')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where('name', 'like', '%' . $searchValue . '%')
                             ->orWhere('company', 'like', '%' . $searchValue . '%')
                             ->orWhere('email', 'like', '%' . $searchValue . '%');
            })
            ->count();

        return $query;
    }
}
