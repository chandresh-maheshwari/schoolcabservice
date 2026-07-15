<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Support\PermissionName;

class Permission extends Model
{
    use HasFactory;
    protected $table = 'permissions';

    protected $fillable = [
        'name',
    ];

    public static function getPermissionData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        // Ensure columnSortOrder is either 'asc' or 'desc'
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc']) ? $columnSortOrder : 'asc';

        $query = DB::table('permissions')
            ->where('permissions.deleted', 0)
            ->where('permissions.name', 'not like', 'api.%')
            ->where('permissions.name', 'not like', 'sanctum.%')
            ->where('permissions.name', 'not like', 'ignition.%')
            ->where('permissions.name', 'not like', 'telescope.%')
            ->where('permissions.name', 'not like', '_debugbar.%')
            ->where('permissions.name', 'not like', 'booking.%')
            ->where('permissions.name', 'not like', 'school.%.%')
            ->where('permissions.name', 'not like', '%.list')
            ->where('permissions.name', 'not like', '%.List')
            ->where('permissions.name', 'not like', '%.deleted-list')
            ->where('permissions.name', 'not like', '%.multi-delete')
            ->where('permissions.name', 'not like', '%.toggleStatus')
            ->where('permissions.name', 'not like', '%.toggle-status')
            ->whereNotIn('permissions.name', PermissionName::hiddenPermissionNames())
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

    public static function getPermissionDataTotal($searchValue)
    {
        $query = DB::table('permissions')
            ->when($searchValue, function ($query, $searchValue) {
                return $query->where('name', 'like', '%' . $searchValue . '%');
            })
            ->where('deleted', 0)
            ->where('name', 'not like', 'api.%')
            ->where('name', 'not like', 'sanctum.%')
            ->where('name', 'not like', 'ignition.%')
            ->where('name', 'not like', 'telescope.%')
            ->where('name', 'not like', '_debugbar.%')
            ->where('name', 'not like', 'booking.%')
            ->where('name', 'not like', 'school.%.%')
            ->where('name', 'not like', '%.list')
            ->where('name', 'not like', '%.List')
            ->where('name', 'not like', '%.deleted-list')
            ->where('name', 'not like', '%.multi-delete')
            ->where('name', 'not like', '%.toggleStatus')
            ->where('name', 'not like', '%.toggle-status')
            ->whereNotIn('name', PermissionName::hiddenPermissionNames())
            ->count();

        return $query;
    }

    /**
     * Get the roles associated with the permission.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }
}
