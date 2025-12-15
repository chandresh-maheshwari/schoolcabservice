<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ClientModel extends Model
{
    use HasFactory;
    protected $table = 'clients';

    protected $fillable = [
        'client',
        'status'
    ];

    public static function getClientData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('clients')
        ->where('clients.deleted', 0)
            // ->select('id', 'client')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where('client', 'like', '%' . $searchValue . '%');
            })
            ->orderBy($columnName, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        return $query;
    }

    public static function getClientDataTotal($searchValue)
    {
        $query = DB::table('clients')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where('client', 'like', '%' . $searchValue . '%');
            })
            ->where('deleted', 0)
            ->count();

        return $query;
    }

}
