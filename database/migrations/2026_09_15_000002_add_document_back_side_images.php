<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vehicles') && ! Schema::hasColumn('vehicles', 'rc_back_image')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->string('rc_back_image')->nullable()->after('rc_image');
            });
        }

        if (Schema::hasTable('drivers')) {
            Schema::table('drivers', function (Blueprint $table) {
                if (! Schema::hasColumn('drivers', 'license_back_image')) {
                    $table->string('license_back_image')->nullable()->after('license_image');
                }
                if (! Schema::hasColumn('drivers', 'adher_card_back_image')) {
                    $table->string('adher_card_back_image')->nullable()->after('adher_card_iamge');
                }
            });
        }

        if (Schema::hasTable('parents')) {
            Schema::table('parents', function (Blueprint $table) {
                if (! Schema::hasColumn('parents', 'father_adhaar_card_back_image')) {
                    $table->string('father_adhaar_card_back_image')->nullable()->after('father_adhaar_card_image');
                }
                if (! Schema::hasColumn('parents', 'mother_adhaar_card_back_image')) {
                    $table->string('mother_adhaar_card_back_image')->nullable()->after('mother_adhaar_card_image');
                }
            });
        }

        if (Schema::hasTable('children') && ! Schema::hasColumn('children', 'child_adhaar_card_back_image')) {
            Schema::table('children', function (Blueprint $table) {
                $table->string('child_adhaar_card_back_image')->nullable()->after('child_adhaar_card_image');
            });
        }
    }

    public function down(): void
    {
        $drops = [
            'vehicles' => ['rc_back_image'],
            'drivers' => ['license_back_image', 'adher_card_back_image'],
            'parents' => ['father_adhaar_card_back_image', 'mother_adhaar_card_back_image'],
            'children' => ['child_adhaar_card_back_image'],
        ];

        foreach ($drops as $tableName => $columns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $existing = array_values(array_filter($columns, fn ($column) => Schema::hasColumn($tableName, $column)));
            if ($existing) {
                Schema::table($tableName, function (Blueprint $table) use ($existing) {
                    $table->dropColumn($existing);
                });
            }
        }
    }
};
