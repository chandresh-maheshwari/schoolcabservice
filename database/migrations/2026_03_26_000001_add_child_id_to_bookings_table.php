<?php

use App\Models\Child;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        if (! Schema::hasColumn('bookings', 'child_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->unsignedBigInteger('child_id')->nullable()->after('id');
                $table->index('child_id');
            });
        }

        $children = Child::query()
            ->with('parent')
            ->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            })
            ->get(['id', 'parent_id', 'school_id', 'route_id']);

        foreach ($children as $child) {
            $contactNumbers = array_values(array_unique(array_filter([
                trim((string) optional($child->parent)->contact_number),
                trim((string) optional($child->parent)->alternative_contact_number),
            ])));

            if (! $child->school_id || ! $child->route_id || empty($contactNumbers)) {
                continue;
            }

            $bookingId = DB::table('bookings')
                ->where(function ($q) {
                    $q->where('deleted', 0)->orWhereNull('deleted');
                })
                ->whereNull('child_id')
                ->where('school_id', (int) $child->school_id)
                ->where('route_id', (int) $child->route_id)
                ->whereIn('contact_number', $contactNumbers)
                ->orderByDesc('id')
                ->value('id');

            if ($bookingId) {
                DB::table('bookings')
                    ->where('id', (int) $bookingId)
                    ->update(['child_id' => (int) $child->id]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings') || ! Schema::hasColumn('bookings', 'child_id')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['child_id']);
            $table->dropColumn('child_id');
        });
    }
};
