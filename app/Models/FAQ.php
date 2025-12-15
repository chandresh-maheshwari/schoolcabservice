<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FAQ extends Model
{
    use HasFactory;
    protected $table = 'faqs';

    protected $fillable = [
        // 'category_id',
        'question',
        'answer',
        'status'
    ];

    public static function getFAQData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('faqs')
            ->where('faqs.deleted', 0)
            // ->join('faq_categories', 'faqs.category_id', '=', 'faq_categories.id')
            // ->select('faqs.id', 'faq_categories.name as category', 'faqs.question', 'faqs.answer')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where('faqs.question', 'like', '%' . $searchValue . '%')
                             ->orWhere('faqs.answer', 'like', '%' . $searchValue . '%');
            })
            ->orderBy($columnName, $columnSortOrder)
            ->skip($row)
            ->limit($rowperpage)
            ->get();

        return $query;
    }

    public static function getFAQDataTotal($searchValue)
    {
        $query = DB::table('faqs')
        ->where('faqs.deleted', 0)
            // ->join('faq_categories', 'faqs.category_id', '=', 'faq_categories.id')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where('faqs.question', 'like', '%' . $searchValue . '%')
                             ->orWhere('faqs.answer', 'like', '%' . $searchValue . '%');
            })
            ->count();

        return $query;
    }
}
