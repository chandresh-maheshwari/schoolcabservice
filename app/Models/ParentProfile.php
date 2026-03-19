<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentProfile extends Model
{
    use HasFactory;

    protected $table = 'parent_profiles';
    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'user_id',
        'parent_id',
        'email',
        'full_name',
        'phone_number',
        'home_address',
        'emergency_contact',
        'alternate_mobile',
        'address',
        'city',
        'state',
        'pincode',
        'notes',
        'parent_name',
        'mobile',
        'emergency_phone',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent()
    {
        return $this->belongsTo(Parents::class, 'parent_id');
    }
}
