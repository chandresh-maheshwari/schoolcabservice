<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;
use MongoDB\Laravel\Eloquent\Model;

use App\Models\User;

class Role extends Model
{
    use HasFactory;

    // protected $table = 'roles';
        protected $collection = 'roles';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];

    static function getRoleData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $query = DB::table('roles')
            ->where('roles.deleted', 0)
            ->select('id', 'name')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where('name', 'like', '%' . $searchValue . '%');
            })
            ->orderBy($columnName, $columnSortOrder)
            ->skip($row)
            ->take($rowperpage)
            ->get();

        return $query;
    }

    static function getRoleDataTotal($searchValue)
    {
        $query = DB::table('roles')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where('name', 'like', '%' . $searchValue . '%');
            })
            ->where('deleted', 0)

            ->count();

        return $query;
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }


    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }
}
