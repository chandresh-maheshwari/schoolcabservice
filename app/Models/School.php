<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class School extends Model
{
    use HasFactory;
    protected $table = 'schools';

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

//     public function drivers()
// {
//     return $this->hasMany(Driver::class);
// }

// public function vehicles()
// {
//     return $this->hasMany(Vehicle::class);
// }

    public static function getSchoolData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('schools')
            ->where('schools.deleted', 0)
        // ->select('id', 'title', 'description as description', 'image')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('schools.school_name', 'like', '%' . $searchValue . '%')
                        ->orWhere('schools.school_code ', 'like', '%' . $searchValue . '%')
                        ->orWhere('schools.phone', 'like', '%' . $searchValue . '%')
                        ->orWhere('schools.email', 'like', '%' . $searchValue . '%')
                        ->orWhere('schools.city', 'like', '%' . $searchValue . '%')
                        ->orWhere('schools.state', 'like', '%' . $searchValue . '%')
                        ->orWhere('schools.pincode', 'like', '%' . $searchValue . '%')
                        ->orWhere('schools.latitude', 'like', '%' . $searchValue . '%')
                        ->orWhere('schools.longitude', 'like', '%' . $searchValue . '%');

                });
            })
            ->orderBy($columnName, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        return $query;
    }

    public static function getSchoolDataTotal($searchValue)
    {
        $query = DB::table('schools')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where(function ($q) use ($searchValue) {
                    $q->where('schools.school_name', 'like', '%' . $searchValue . '%')
                        ->orWhere('schools.school_code', 'like', '%' . $searchValue . '%')
                        ->orWhere('schools.phone', 'like', '%' . $searchValue . '%')
                        ->orWhere('schools.email', 'like', '%' . $searchValue . '%')
                        ->orWhere('schools.city', 'like', '%' . $searchValue . '%')
                        ->orWhere('schools.state', 'like', '%' . $searchValue . '%')
                        ->orWhere('schools.pincode', 'like', '%' . $searchValue . '%')
                        ->orWhere('schools.latitude', 'like', '%' . $searchValue . '%')
                        ->orWhere('schools.longitude', 'like', '%' . $searchValue . '%');
                });
            })
            ->where('schools.deleted', 0)
            ->count();

        return $query;
    }
}
