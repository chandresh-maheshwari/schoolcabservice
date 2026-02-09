<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaqSection extends Model
{
    protected $table = 'faq_sections';

    protected $fillable = [
        'name',
        'question',
        'answer',
        'status',
        'deleted',
    ];

      protected $attributes = [
        'status'  => 0,
        'deleted' => 0,
    ];

    public static function getFaqData(
        $searchValue,
        $columnName,
        $columnSortOrder,
        $row,
        $rowperpage
    ) {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc'])
            ? $columnSortOrder
            : 'asc';

        $allowedColumns = [
            'id',
            'name',
            'question',
            'answer',
            'status',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : 'id';

        $query = self::where('deleted', 0);

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('question', 'like', "%$searchValue%")
                  ->orWhere('answer', 'like', "%$searchValue%");
            });
        }

        return $query
            ->orderBy($columnName, $columnSortOrder)
            ->skip((int) $row)
            ->take((int) $rowperpage)
            ->get();
    }

    public static function getFaqDataTotal($searchValue)
    {
        $query = self::where('deleted', 0);

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('question', 'like', "%$searchValue%")
                  ->orWhere('answer', 'like', "%$searchValue%");
            });
        }

        return $query->count();
    }
}
