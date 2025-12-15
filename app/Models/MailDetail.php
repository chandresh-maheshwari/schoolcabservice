<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailDetail extends Model
{
    use HasFactory;
    protected $guard_name = 'api';
    protected $table = 'email_details';
    protected $fillable = ['user_id','email_type','email_to','mail_details', 'is_sent'];

    protected $casts = [
        'created_at' => 'datetime:F jS, Y',
        'updated_at' => 'datetime:F jS, Y',
    ];

    public function hasUser()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
