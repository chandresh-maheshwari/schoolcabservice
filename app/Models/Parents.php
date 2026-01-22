<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parents extends Model
{

    use HasFactory;

    // protected $connection = 'mongodb';
    protected $table = 'parents';

    protected $fillable = [
        'father_name',
        'mother_name',
        'email',
        'address_1',
        'address_2',
        'state',
        'city',
        'pincode',
        'father_adhaar_card_image',
        'mother_adhaar_card_image',
        'contact_number',
        'alternative_contact_number',
        'status',
        'deleted',
    ];

    protected $attributes = [
        'status'  => 0,
        'deleted' => 0,
    ];

    public static function getParentData(
        $searchValue,
        $columnName,
        $columnSortOrder,
        $draw,
        $row,
        $rowperpage
    ) {
        // Secure sort order
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc'])
            ? $columnSortOrder
            : 'asc';

        // Allowed sortable columns
        $allowedColumns = [
            'id',
            'father_name',
            'mother_name',
            'email',
            'address_1',
            'address_2',
            'state',
            'city',
            'pincode',
            'father_adhaar_card_image',
            'mother_adhaar_card_image',
            'contact_number',
            'alternative_contact_number',
            'status',
            'deleted',
            'created_at',
            'updated_at',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : 'id';

        // Base query (exclude deleted)
        $query = self::where(function ($q) {
            $q->where('deleted', 0)
                ->orWhereNull('deleted');
        });

        // Search filter
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->Where('father_name', 'like', "%$searchValue%")
                    ->orWhere('mother_name', 'like', "%$searchValue%")
                    ->orWhere('contact_number', 'like', "%$searchValue%")
                    ->orWhere('email', 'like', "%$searchValue%")
                    ->orWhere('city', 'like', "%$searchValue%")
                    ->orWhere('state', 'like', "%$searchValue%")
                    ->orWhere('pincode', 'like', "%$searchValue%");
            });
        }

        // Pagination + Sorting
        return $query
            ->orderBy($columnName, $columnSortOrder)
            ->skip((int) $row)
            ->take((int) $rowperpage)
            ->get();
    }

    public static function getParentDataTotal($searchValue)
    {
        $query = self::where(function ($q) {
            $q->where('deleted', 0)
                ->orWhereNull('deleted');
        });

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->Where('father_name', 'like', "%$searchValue%")
                    ->orWhere('mother_name', 'like', "%$searchValue%")
                    ->orWhere('contact_number', 'like', "%$searchValue%")
                    ->orWhere('email', 'like', "%$searchValue%")
                    ->orWhere('city', 'like', "%$searchValue%")
                    ->orWhere('state', 'like', "%$searchValue%")
                    ->orWhere('pincode', 'like', "%$searchValue%");
            });
        }

        return $query->count();
    }

}
