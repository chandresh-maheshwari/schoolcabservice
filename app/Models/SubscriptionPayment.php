<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    use HasFactory;

    protected $table = 'subscription_payments';

    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'child_subscription_id',
        'channel',
        'status',
        'amount',
        'currency',
        'order_id',
        'payment_id',
        'signature',
        'receipt_no',
        'reference_no',
        'collected_by_user_id',
        'paid_at',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'paid_at' => 'datetime',
    ];

    public function subscription()
    {
        return $this->belongsTo(ChildSubscription::class, 'child_subscription_id');
    }
}
