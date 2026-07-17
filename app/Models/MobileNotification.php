<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MobileNotification extends Model
{
    use HasFactory;

    protected $table = 'mobile_notifications';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'message',
        'type',
        'payload',
        'data',
        'is_read',
        'sent_at',
        'createdAt',
        'updated_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'data' => 'array',
        'is_read' => 'boolean',
        'sent_at' => 'datetime',
        'createdAt' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function pruneExpiredRecords(?Carbon $cutoff = null): int
    {
        // Mobile notifications should remain in the inbox until an explicit
        // product decision introduces user-facing cleanup or archival.
        return 0;
    }
}
