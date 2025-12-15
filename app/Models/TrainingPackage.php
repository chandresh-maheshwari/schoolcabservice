<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingPackage extends Model
{
    use HasFactory;
     protected $table = 'inquiry_training';
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'contact_number',
        'description',
        'technologies',
        'cv',
    ];
}
