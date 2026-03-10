<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
// use App\Models\WriterProfile;
use App\Models\Role;
use MongoDB\Laravel\Eloquent\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;
// use MongoDB\Laravel\Auth\User as Authenticatable;


class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
// protected $collection = 'users'; // Mongo
    protected $table = 'users'; // SQL
    protected $fillable = [
        'first_name',
        'last_name',
        'mobile',
        'email',
        'username',
        'photo',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
        //  return (string) $this->_id;
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

//     public function role()
// {
//     return $this->belongsTo(Role::class, 'role_id', '_id');
// }
    public static function getuserdata($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $sql = DB::table('users')
            ->select('id', 'first_name', 'last_name', 'mobile', 'email', 'photo', 'role_id', 'status')
            ->where('deleted', 0);

        if (!empty($searchValue)) {
            $sql->where(function($query) use ($searchValue) {
                $query->where('first_name', 'like', '%' . $searchValue . '%')
                      ->orWhere('last_name', 'like', '%' . $searchValue . '%')
                      ->orWhere('mobile', 'like', '%' . $searchValue . '%')
                      ->orWhere('email', 'like', '%' . $searchValue . '%')
                      ->orWhere('status', 'like', '%' . $searchValue . '%');
            });
        }

        // Apply column sorting if column name is provided
        if (!empty($columnName)) {
            $sql->orderBy($columnName, $columnSortOrder ?? 'asc');
        } else {
            $sql->orderBy('id', 'desc');
        }

        $query = $sql->skip($row)->take($rowperpage)->get();

        return $query;
    }

    public static function getuserdataTotal($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage)
    {
        $sql = DB::table('users')
            ->select('id')
            ->where('deleted', 0);

        if (!empty($searchValue)) {
            $sql->where(function($query) use ($searchValue) {
                $query->where('first_name', 'like', '%' . $searchValue . '%')
                      ->orWhere('last_name', 'like', '%' . $searchValue . '%')
                      ->orWhere('mobile', 'like', '%' . $searchValue . '%')
                      ->orWhere('email', 'like', '%' . $searchValue . '%')
                      ->orWhere('status', 'like', '%' . $searchValue . '%');
            });
        }

        // Apply column sorting if column name is provided
        if (!empty($columnName)) {
            $sql->orderBy($columnName, $columnSortOrder ?? 'asc');
        } else {
            $sql->orderBy('id', 'desc');
        }

        $query = $sql->count();
        return $query;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // public function writerProfile()
    // {
    //     return $this->hasOne(WriterProfile::class);
    // }

    public function follow($userIdToFollow)
    {
        $following = is_string($this->following) ? json_decode($this->following, true) : $this->following;
        $following = is_array($following) ? $following : [];

        if (!in_array($userIdToFollow, $following)) {
            $following[] = $userIdToFollow;
            $this->following = json_encode($following);
            $this->save();
        }

        $userToFollow = User::find($userIdToFollow);
        if ($userToFollow) {
            $userToFollow->addFollower($this->id);
        }
    }

    public function addFollower($followerId)
    {
        $followers = is_string($this->followers) ? json_decode($this->followers, true) : $this->followers;
        $followers = is_array($followers) ? $followers : [];

        if (!in_array($followerId, $followers)) {
            $followers[] = $followerId;
            $this->followers = json_encode($followers);
            $this->save();
        }
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'followers', 'user_id', 'follower_id');
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'user_id');
    }

    public function getFollowersAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }

    public function getFollowingAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }

    public function followerDetails()
    {
        return $this->hasMany(User::class, 'id', 'followers');
    }

    public function followingDetails()
    {
        return $this->hasMany(User::class, 'id', 'following');
    }

    /**
     * Check if the user has a specific permission through their role
     *
     * @param string $permissionName
     * @return bool
     */
    public function hasPermission($permissionName)
    {
        if (!$this->role_id) {
            return false;
        }

        $role = Role::find($this->role_id);
        if (!$role) {
            return false;
        }

        return $role->permissions()->where('name', $permissionName)->exists();
    }

    /**
     * Alias for hasPermission method to maintain compatibility with middleware
     *
     * @param string $permissionName
     * @return bool
     */
    public function hasPermissionTo($permissionName)
    {
        return $this->hasPermission($permissionName);
    }

    public function hasRole(string $roleName): bool
    {
        $role = $this->role;

        if (! $role || ! isset($role->name)) {
            return false;
        }

        return strcasecmp((string) $role->name, $roleName) === 0;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('Admin') || $this->isSuperAdmin();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin') || (int) $this->role_id === 13;
    }

    public function isSchool(): bool
    {
        return $this->hasRole('School');
    }

    public function canAccessAdminRoute(?string $routeName): bool
    {
        if (! $routeName) {
            return true;
        }

        $alwaysAllowedRoutes = [
            'logout.user',
            'admin.profile',
            'profile.edit',
            'profile.update',
            'admin_layout.index',
            'school.dashboard',
            'school.profile.edit',
            'school.profile.update',
        ];

        $originalRouteName = $routeName;
        if (in_array($originalRouteName, $alwaysAllowedRoutes, true)) {
            return true;
        }

        // School panel routes are named like `school.vehicle.index` but permissions are stored
        // against the base route names (e.g. `vehicle.index`).
        if (str_starts_with($routeName, 'school.')) {
            $routeName = substr($routeName, strlen('school.'));
        }

        // Keep Role/Permission management reachable even if a Super Admin role is misconfigured.
        // Everything else is still permission-controlled.
        if ($this->isSuperAdmin()) {
            if (str_starts_with($routeName, 'roles.') || str_starts_with($routeName, 'permissions.')) {
                return true;
            }
        }

        if ($this->hasPermissionTo($routeName)) {
            return true;
        }

        if (! $this->isSchool()) {
            return false;
        }

        // School users can always manage their own school profile (branding/config).
        $schoolExplicitAllowedRoutes = [
            'school.edit',
            'school.update',
            'school.getCities',
            'school.getPincode',
        ];

        return in_array($routeName, $schoolExplicitAllowedRoutes, true);
    }

    public function unfollow($userIdToUnfollow)
    {
        $following = is_string($this->following) ? json_decode($this->following, true) : $this->following;
        $following = is_array($following) ? $following : [];

        if (in_array($userIdToUnfollow, $following)) {
            $following = array_diff($following, [$userIdToUnfollow]);
            $this->following = json_encode(array_values($following));
            $this->save();
        }

        $userToUnfollow = User::find($userIdToUnfollow);
        if ($userToUnfollow) {
            $userToUnfollow->removeFollower($this->id);
        }
    }

    public function removeFollower($followerId)
    {
        $followers = is_string($this->followers) ? json_decode($this->followers, true) : $this->followers;
        $followers = is_array($followers) ? $followers : [];

        if (in_array($followerId, $followers)) {
            $followers = array_diff($followers, [$followerId]);
            $this->followers = json_encode(array_values($followers));
            $this->save();
        }
    }

    /**
     * The roles that belong to the user.
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
