<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('child_subscriptions')) {
            return;
        }
        DB::table('child_subscriptions')
            ->whereRaw("LOWER(TRIM(package_type)) IN ('daily', '1day', '1 day')")
            ->whereNotNull('starts_at')
            ->whereIn('status', ['active', 'expired'])
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) {
                foreach ($subscriptions as $subscription) {
                    $expiry = Carbon::parse($subscription->starts_at, 'Asia/Kolkata')->endOfDay();
                    DB::table('child_subscriptions')->where('id', $subscription->id)->update([
                        'expires_at' => $expiry->format('Y-m-d H:i:s'),
                        'status' => $expiry->isPast() ? 'expired' : 'active',
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Preserve corrected expiry dates.
    }
};
