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

    public static function pruneExpiredRecords(?Carbon $cutoff = null): int
    {
        $instance = new static();
        $table = $instance->getTable();

        if (! Schema::hasTable($table)) {
            return 0;
        }

        $columns = Schema::getColumnListing($table);
        $createdColumn = in_array('createdAt', $columns, true)
            ? 'createdAt'
            : (in_array('created_at', $columns, true) ? 'created_at' : null);

        if (! $createdColumn) {
            return 0;
        }

        return DB::table($table)
            ->where($createdColumn, '<', $cutoff ?? now()->subDays(2))
            ->delete();
    }
}
