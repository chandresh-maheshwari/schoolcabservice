<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChildSubscription extends Model
{
    use HasFactory;

    protected $table = 'child_subscriptions';

    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'child_id',
        'service_type',
        'package_type',
        'status',
        'source',
        'is_current',
        'starts_at',
        'expires_at',
        'created_by_user_id',
        'notes',
    ];

    public function child()
    {
        return $this->belongsTo(Child::class, 'child_id');
    }

    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class, 'child_subscription_id');
    }
}
