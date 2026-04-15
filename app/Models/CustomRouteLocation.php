<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomRouteLocation extends Model
{
    use HasFactory;

    protected $table = 'custom_route_locations';

    protected $fillable = [
        'user_id',
        'school_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'status',
        'deleted',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];
}
