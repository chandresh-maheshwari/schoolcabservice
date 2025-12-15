<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PricingModel extends Model
{
    use HasFactory;
    protected $table = 'pricing';

    protected $fillable = [
        'title',
        'currency',
        'amount',
        'period',
        'description',
        'feature_title',
        'feature_1',
        'feature_2',
        'feature_3',
        'feature_4',
        'feature_5',
        'feature_6',        
        'button_title',
        'button_link',
        'status',
        'deleted'
    ];

    public static function getPricingData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('pricing')
            ->where('deleted', 0)
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('title', 'like', '%' . $searchValue . '%')
                      ->orWhere('currency', 'like', '%' . $searchValue . '%')
                      ->orWhere('amount', 'like', '%' . $searchValue . '%')
                      ->orWhere('period', 'like', '%' . $searchValue . '%')
                      ->orWhere('description', 'like', '%' . $searchValue . '%');
                });
            })
            ->orderBy($columnName, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        return $query;
    }

    public static function getPricingDataTotal($searchValue)
    {
        $query = DB::table('pricing')            
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('title', 'like', '%' . $searchValue . '%')
                      ->orWhere('currency', 'like', '%' . $searchValue . '%')
                      ->orWhere('amount', 'like', '%' . $searchValue . '%')
                      ->orWhere('period', 'like', '%' . $searchValue . '%')
                      ->orWhere('description', 'like', '%' . $searchValue . '%');
                });
            })
            ->where('pricing.deleted', 0)
            ->count();

        return $query;
    }

}
