<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Model;
use MongoDB\Laravel\Eloquent\Model;


class Route extends Model
{
    //
    protected $connection = 'mongodb';
    protected $collection = 'routes';

    protected $fillable = [
        // 'school_id',
        'name',
        'bus_id',
        'driver_id',
        'geojson',
        'stops',
        'deleted',
        'created_at'
    ];

    protected $casts = [
        'geojson' => 'array',
        'stops'   => 'array',
    ];

    public $timestamps = false;
    // Route belongs to Vehicle
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'bus_id', '_id');
    }

    // Route belongs to Driver
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id', '_id');
    }
}
