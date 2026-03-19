<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileNotification extends Model
{
    use HasFactory;

    protected $table = 'mobile_notifications';

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'type',
        'payload',
        'is_read',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'is_read' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
