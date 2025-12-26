<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Model;
use MongoDB\Laravel\Eloquent\Model;

class State extends Model
{
    // protected $table = 'states';
     protected $collection = 'states';
    protected $fillable = ['name'];
}
