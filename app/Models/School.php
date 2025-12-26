<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Eloquent\Model;

class School extends Model
{
    use HasFactory;
    // protected $table = 'schools';
    protected $collection = 'schools';

    protected $fillable = [
        'school_name',
        'school_code',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'pincode',
        'latitude',
        'longitude',
        'status',
    ];

    protected $casts = [
        'latitude'  => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    protected $attributes = [
    'status'  => 0,
    'deleted' => 0,
];

//     public function drivers()
// {
//     return $this->hasMany(Driver::class);
// }

// public function vehicles()
// {
//     return $this->hasMany(Vehicle::class);
// }

    public static function getSchoolData($searchValue,$columnName, $columnSortOrder, $draw, $row, $rowperpage
    ) {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc'])
            ? $columnSortOrder
            : 'asc';

        $allowedColumns = [
            '_id',
            'school_name',
            'school_code',
            'phone',
            'email',
            'address',
            'city',
            'state',
            'pincode',
            'latitude',
            'longitude',
            'status',
            'deleted',
            'created_at',
            'updated_at',

        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : '_id';

        $query = self::where(function ($q) {
            $q->where('deleted', 0)
                ->orWhereNull('deleted');
        });

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('school_name', 'like', "%$searchValue%")
                    ->orWhere('school_code', 'like', "%$searchValue%")
                    ->orWhere('phone', 'like', "%$searchValue%")
                    ->orWhere('email', 'like', "%$searchValue%")
                    ->orWhere('city', 'like', "%$searchValue%")
                    ->orWhere('state', 'like', "%$searchValue%")
                    ->orWhere('pincode', 'like', "%$searchValue%")
                    ->orWhere('latitude', 'like', "%$searchValue%")
                    ->orWhere('longitude', 'like', "%$searchValue%");
            });
        }

        return $query
            ->orderBy($columnName, $columnSortOrder)
            ->skip((int) $row)
            ->take((int) $rowperpage)
            ->get();
    }

  public static function getSchoolDataTotal($searchValue)
{

    $query = self::where(function ($q) {
        $q->where('deleted', 0)
          ->orWhereNull('deleted');
    });

    if (!empty($searchValue)) {
        $query->where(function ($q) use ($searchValue) {
            $q->where('school_name', 'like', "%$searchValue%")
              ->orWhere('school_code', 'like', "%$searchValue%")
              ->orWhere('phone', 'like', "%$searchValue%")
              ->orWhere('email', 'like', "%$searchValue%")
              ->orWhere('city', 'like', "%$searchValue%")
              ->orWhere('state', 'like', "%$searchValue%")
              ->orWhere('pincode', 'like', "%$searchValue%")
              ->orWhere('latitude', 'like', "%$searchValue%")
              ->orWhere('longitude', 'like', "%$searchValue%");
        });
    }

    return $query->count();
}

}
