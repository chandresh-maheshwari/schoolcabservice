<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ContactMessageSection extends Model
{
    protected $table = 'contact_messages';

    protected $fillable = [
        'name',
        'email',
        'message',
        'company',
    ];

    public static function getContactMessages($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('contact_messages')
            ->select('id', 'name', 'email', 'message' , 'company')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where('name', 'like', '%' . $searchValue . '%')
                             ->orWhere('email', 'like', '%' . $searchValue . '%')
                             ->orWhere('message', 'like', '%' . $searchValue . '%')
                             ->orWhere('company', 'like', '%' . $searchValue . '%');
            })
            ->orderBy($columnName, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        return $query;
    }

    public static function getContactMessagesTotal($searchValue)
    {
        $query = DB::table('contact_messages')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where('name', 'like', '%' . $searchValue . '%')
                             ->orWhere('email', 'like', '%' . $searchValue . '%')
                             ->orWhere('message', 'like', '%' . $searchValue . '%')
                             ->orWhere('company', 'like', '%' . $searchValue . '%');
            })
            ->count();

        return $query;
    }
}
