<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ContactMessage extends Model
{
    use HasFactory;
    protected $table = 'contact_messages';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'subject',
        'message',
    ];

    public static function getContactMessages($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('contact_messages')
            ->select('id', 'first_name', 'last_name', 'email', 'subject', 'message')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where('first_name', 'like', '%' . $searchValue . '%')
                             ->orWhere('last_name', 'like', '%' . $searchValue . '%')
                             ->orWhere('email', 'like', '%' . $searchValue . '%')
                             ->orWhere('subject', 'like', '%' . $searchValue . '%')
                             ->orWhere('message', 'like', '%' . $searchValue . '%');
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
                return $query->where('first_name', 'like', '%' . $searchValue . '%')
                             ->orWhere('last_name', 'like', '%' . $searchValue . '%')
                             ->orWhere('email', 'like', '%' . $searchValue . '%')
                             ->orWhere('subject', 'like', '%' . $searchValue . '%')
                             ->orWhere('message', 'like', '%' . $searchValue . '%');
            })
            ->count();

        return $query;
    }
} 