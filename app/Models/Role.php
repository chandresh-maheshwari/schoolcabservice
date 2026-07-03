<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Permission;
// use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Model;

use App\Models\User;

class Role extends Model
{
    use HasFactory;

    private const HIDDEN_ROLE_NAMES = ['admin', 'super admin'];

    protected $table = 'roles';
        // protected $collection = 'roles';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];

    public function scopeNotDeleted($query)
    {
        if (Schema::hasColumn($this->getTable(), 'is_delete')) {
            return $query->where('is_delete', 0);
        }

        return $query->where('deleted', 0);
    }

    static function getRoleData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $query = DB::table('roles')
            ->where('roles.deleted', 0)
            ->whereNotIn(DB::raw('LOWER(name)'), self::HIDDEN_ROLE_NAMES)
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
            ->where('deleted', 0)
            ->whereNotIn(DB::raw('LOWER(name)'), self::HIDDEN_ROLE_NAMES)
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where('name', 'like', '%' . $searchValue . '%');
            })
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
