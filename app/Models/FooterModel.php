<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FooterModel extends Model
{
    use HasFactory;
    protected $table = 'footer';

    protected $fillable = [
        'title',
        'footer_link',
        'location',
        'contact_title',
        'contact',
        'email_title',
        'email',

        'footer_link_title',
        'page_title_1',
        'page_link_1',
        'page_title_2',
        'page_link_2',
        'page_title_3',
        'page_link_3',
        'page_title_4',
        'page_link_4',

        'footer_service_title',
        'service_title_1',
        'service_link_1',
        'service_title_2',
        'service_link_2',
        'service_title_3',
        'service_link_3',
        'service_title_4',
        'service_link_4',

        'follow_us',
        'description',
        'contact_1',
        'contact_2',
        'email_1',
        'email_2',
        'copy_right_text',
        'status',
        'deleted',
    ];

    public static function getFooterData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('footer')
            ->where('footer.deleted', 0)
        // ->select('id', 'title', 'description', 'alternative_icon')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('title', 'like', '%' . $searchValue . '%');
                    //   ->orWhere('description', 'like', '%' . $searchValue . '%')
                    //   ->orWhere('location_title', 'like', '%' . $searchValue . '%')
                    //   ->orWhere('location', 'like', '%' . $searchValue . '%')
                    //   ->orWhere('contact_title', 'like', '%' . $searchValue . '%')
                    //   ->orWhere('contact_1', 'like', '%' . $searchValue . '%')
                    //   ->orWhere('contact_2', 'like', '%' . $searchValue . '%')
                    //   ->orWhere('email_title', 'like', '%' . $searchValue . '%')
                    //   ->orWhere('email_1', 'like', '%' . $searchValue . '%')
                    //   ->orWhere('email_2', 'like', '%' . $searchValue . '%');
                });
            })
            ->orderBy($columnName, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        return $query;
    }

    public static function getFooterDataTotal($searchValue)
    {
        $query = DB::table('footer')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('title', 'like', '%' . $searchValue . '%');
                    //   ->orWhere('description', 'like', '%' . $searchValue . '%')
                    //   ->orWhere('location_title', 'like', '%' . $searchValue . '%')
                    //   ->orWhere('location', 'like', '%' . $searchValue . '%')
                    //   ->orWhere('contact_title', 'like', '%' . $searchValue . '%')
                    //   ->orWhere('contact_1', 'like', '%' . $searchValue . '%')
                    //   ->orWhere('contact_2', 'like', '%' . $searchValue . '%')
                    //   ->orWhere('email_title', 'like', '%' . $searchValue . '%')
                    //   ->orWhere('email_1', 'like', '%' . $searchValue . '%')
                    //   ->orWhere('email_2', 'like', '%' . $searchValue . '%');
                });
            })
            ->where('deleted', 0)
            ->count();

        return $query;
    }

}
