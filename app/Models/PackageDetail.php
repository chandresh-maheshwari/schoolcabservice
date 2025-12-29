<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class PackageDetail extends Model
{

    use HasFactory;
    protected $collection = 'package_details';

    protected $fillable = [
        'package_name',
        'package_type',
        'booking_type',
        'price',
        'validity_days',
        'short_description',
        'description',
        'status',
        'deleted',
    ];

    protected $attributes = [
        'status'  => 0,
        'deleted' => 0,
    ];

    public static function getPackageData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage
    ) {
        // Secure sort order
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc'])
            ? $columnSortOrder
            : 'asc';

        // Allowed columns
        $allowedColumns = [
            '_id',
            'package_name',
            'package_type',
            'booking_type',
            'price',
            'validity_days',
            'short_description',
            'description',
            'status',
            'deleted',
            'created_at',
            'updated_at',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : '_id';

        //  Base query (exclude deleted)
       $query = self::where('deleted', 0);

        //  Search filter
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('package_name', 'like', "%$searchValue%")
                    ->orWhere('package_type', 'like', "%$searchValue%")
                    ->orWhere('booking_type', 'like', "%$searchValue%")
                    ->orWhere('price', 'like', "%$searchValue%")
                    ->orWhere('validity_days', 'like', "%$searchValue%")
                    ->orWhere('short_description', 'like', "%$searchValue%")
                    ->orWhere('description', 'like', "%$searchValue%");
            });
        }

        //  Pagination + Sorting
        return $query
            ->orderBy($columnName, $columnSortOrder)
            ->skip((int) $row)
            ->take((int) $rowperpage)
            ->get();
    }

    public static function getPackageDataTotal($searchValue)
    {
         $query = self::where('deleted', 0);

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('package_name', 'like', "%$searchValue%")
                    ->orWhere('package_type', 'like', "%$searchValue%")
                    ->orWhere('booking_type', 'like', "%$searchValue%")
                    ->orWhere('price', 'like', "%$searchValue%")
                    ->orWhere('validity_days', 'like', "%$searchValue%")
                    ->orWhere('short_description', 'like', "%$searchValue%")
                    ->orWhere('description', 'like', "%$searchValue%");
            });
        }

        return $query->count();
    }
}
