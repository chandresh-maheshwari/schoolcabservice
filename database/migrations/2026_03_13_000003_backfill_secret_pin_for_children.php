<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('children') || ! Schema::hasColumn('children', 'secret_pin')) {
            return;
        }

        DB::table('children')
            ->select(['id'])
            ->where(function ($q) {
                $q->whereNull('secret_pin')->orWhere('secret_pin', '');
            })
            ->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            })
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('children')
                        ->where('id', (int) $row->id)
                        ->update(['secret_pin' => (string) random_int(1000, 9999)]);
                }
            });
    }

    public function down(): void
    {
        // No-op: we don't want to unset generated PINs.
    }
};

