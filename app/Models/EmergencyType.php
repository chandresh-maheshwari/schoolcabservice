<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyType extends Model
{
    use HasFactory;

    protected $table = 'emergency_types';

    protected $fillable = [
        'user_id',
        'school_id',
        'emergency_type',
        'status',
        'deleted',
        'deleted_at',
    ];

    protected $attributes = [
        'status' => 0,
        'deleted' => 0,
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];
}
