<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HowItWork extends Model
{
    protected $table = 'how_it_works';

    protected $fillable = [
        'title',
        'name',
        'description',
        'button_name_1',
        'button_link_1',
        'button_name_2',
        'button_link_2',
        'status',
        'deleted',
    ];
    // protected $casts = [
    //     'status'  => 0,
    //     'deleted' => 0,
    // ];

    public static function getHowItWorkData(
        $searchValue,
        $columnName,
        $columnSortOrder,
        $row,
        $rowperpage
    ) {
        // Secure sort order
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc'])
            ? $columnSortOrder
            : 'asc';

        // Allowed columns for sorting
        $allowedColumns = [
            'id',
            'title',
            'name',
            'description',
            'button_name_1',
            'button_link_1',
            'button_name_2',
            'button_link_2',
            'status',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : 'id';

        // Base query (exclude deleted)
        $query = self::where('deleted', 0);

        // Search filter
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('title', 'like', "%$searchValue%")
                    ->orWhere('name', 'like', "%$searchValue%")
                    ->orWhere('description', 'like', "%$searchValue%")
                    ->orWhere('button_name_1', 'like', "%$searchValue%")
                    ->orWhere('button_link_1', 'like', "%$searchValue%")
                    ->orWhere('button_name_2', 'like', "%$searchValue%")
                    ->orWhere('button_link_2', 'like', "%$searchValue%");
            });
        }

        // Pagination + Sorting
        return $query
            ->orderBy($columnName, $columnSortOrder)
            ->skip((int) $row)
            ->take((int) $rowperpage)
            ->get();
    }
    public static function getHowItWorkDataTotal($searchValue)
    {
        $query = self::where('deleted', 0);

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('title', 'like', "%$searchValue%")
                    ->orWhere('name', 'like', "%$searchValue%")
                    ->orWhere('description', 'like', "%$searchValue%")
                    ->orWhere('button_name_1', 'like', "%$searchValue%")
                    ->orWhere('button_link_1', 'like', "%$searchValue%")
                    ->orWhere('button_name_2', 'like', "%$searchValue%")
                    ->orWhere('button_link_2', 'like', "%$searchValue%");
            });
        }

        return $query->count();
    }
}
