<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ContactModel extends Model
{
    use HasFactory;
    protected $table = 'contact';

    protected $fillable = [
        'title',
        'description',
        'location_title',
        'location',
        'contact_title',
        'contact_1',
        'contact_2',
        'email_title',
        'email_1',
        'email_2',
        'contact_form_title',
        'contact_form_description',
        'status',
        'deleted'
    ];

    public static function getContactsData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('contact')
            ->where('contact.deleted', 0)
            // ->select('id', 'title', 'description', 'alternative_icon')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('title', 'like', '%' . $searchValue . '%')
                      ->orWhere('description', 'like', '%' . $searchValue . '%')
                      ->orWhere('location_title', 'like', '%' . $searchValue . '%')
                      ->orWhere('location', 'like', '%' . $searchValue . '%')
                      ->orWhere('contact_title', 'like', '%' . $searchValue . '%')
                      ->orWhere('contact_1', 'like', '%' . $searchValue . '%')
                      ->orWhere('contact_2', 'like', '%' . $searchValue . '%')
                      ->orWhere('email_title', 'like', '%' . $searchValue . '%')
                      ->orWhere('email_1', 'like', '%' . $searchValue . '%')
                      ->orWhere('email_2', 'like', '%' . $searchValue . '%');
                });
            })
            ->orderBy($columnName, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        return $query;
    }

    public static function getContactsDataTotal($searchValue)
    {
        $query = DB::table('contact')
            ->when($searchValue, function ($query, $searchValue) {
                  return $query->where(function ($q) use ($searchValue) {
                    $q->where('title', 'like', '%' . $searchValue . '%')
                      ->orWhere('description', 'like', '%' . $searchValue . '%')
                      ->orWhere('location_title', 'like', '%' . $searchValue . '%')
                      ->orWhere('location', 'like', '%' . $searchValue . '%')
                      ->orWhere('contact_title', 'like', '%' . $searchValue . '%')
                      ->orWhere('contact_1', 'like', '%' . $searchValue . '%')
                      ->orWhere('contact_2', 'like', '%' . $searchValue . '%')
                      ->orWhere('email_title', 'like', '%' . $searchValue . '%')
                      ->orWhere('email_1', 'like', '%' . $searchValue . '%')
                      ->orWhere('email_2', 'like', '%' . $searchValue . '%');
                });
            })
            ->where('deleted', 0)
            ->count();

        return $query;
    }

}
