<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingPlanSection extends Model
{
      protected $table = 'pricing_plans';
    protected $fillable = [
        'title',
        'plan_icon',
        'currency_icon',
        'amount',
        'period',
        'description',
        'button_name',
        'button_link',
        'is_most_popular',
        'status',
        'deleted',
    ];
    protected $attributes = [
        'status'  => 0,
        'deleted' => 0,
    ];

    public static function getPricingPlanData(
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

        // Allowed columns (Pricing Plan fields)
        $allowedColumns = [
            'id',
            'title',
            'amount',
            'period',
            'is_most_popular',
            'status',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : 'id';

        // Base query (if deleted column exists)
        $query = self::where('deleted', 0);

        // 🔍 Search filter
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('title', 'like', "%$searchValue%")
                    ->orWhere('period', 'like', "%$searchValue%")
                    ->orWhere('amount', 'like', "%$searchValue%")
                    ->orWhere('period', 'like', "%$searchValue%")
                    ->orWhere('is_most_popular', 'like', "%$searchValue%");
            });
        }

        // Pagination + Sorting
        return $query
            ->orderBy($columnName, $columnSortOrder)
            ->skip((int) $row)
            ->take((int) $rowperpage)
            ->get();
    }
    public static function getPricingPlanDataTotal($searchValue)
    {
        $query = self::where('deleted', 0);

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('title', 'like', "%$searchValue%")
                    ->orWhere('period', 'like', "%$searchValue%")
                    ->orWhere('amount', 'like', "%$searchValue%")
                    ->orWhere('period', 'like', "%$searchValue%")
                    ->orWhere('is_most_popular', 'like', "%$searchValue%");
            });
        }

        return $query->count();
    }
}
