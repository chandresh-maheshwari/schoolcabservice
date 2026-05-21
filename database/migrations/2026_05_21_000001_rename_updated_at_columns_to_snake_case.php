<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $tables = [
        'child_subscriptions',
        'device_tokens',
        'driver_checklists',
        'driver_emergencies',
        'emergency_contacts',
        'leave_requests',
        'login_otps',
        'mobile_notifications',
        'parent_profiles',
        'push_notification_event_logs',
        'push_notification_settings',
        'subscription_payments',
        'support_requests',
        'trips',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            $this->renameColumnIfNeeded($table, 'updatedAt', 'updated_at');
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            $this->renameColumnIfNeeded($table, 'updated_at', 'updatedAt');
        }
    }

    private function renameColumnIfNeeded(string $table, string $from, string $to): void
    {
        $fromColumn = $this->columnDefinition($table, $from);

        if (! $fromColumn || $this->columnDefinition($table, $to)) {
            return;
        }

        $null = strtoupper((string) $fromColumn->IS_NULLABLE) === 'YES' ? 'NULL' : 'NOT NULL';
        $defaultValue = $fromColumn->COLUMN_DEFAULT;
        $default = ($defaultValue === null || strtoupper((string) $defaultValue) === 'NULL')
            ? ''
            : ' DEFAULT ' . ($this->isSqlExpressionDefault((string) $defaultValue)
                ? (string) $defaultValue
                : DB::getPdo()->quote((string) $defaultValue));
        $extra = trim((string) $fromColumn->EXTRA);
        $extraSql = $extra !== '' ? ' ' . $extra : '';

        DB::statement(sprintf(
            'ALTER TABLE `%s` CHANGE `%s` `%s` %s %s%s%s',
            str_replace('`', '``', $table),
            str_replace('`', '``', $from),
            str_replace('`', '``', $to),
            $fromColumn->COLUMN_TYPE,
            $null,
            $default,
            $extraSql
        ));
    }

    private function columnDefinition(string $table, string $column): ?object
    {
        $rows = DB::select(
            'SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
             LIMIT 1',
            [$table, $column]
        );

        return $rows[0] ?? null;
    }

    private function isSqlExpressionDefault(string $default): bool
    {
        return preg_match('/^(CURRENT_TIMESTAMP|current_timestamp)(\(\))?$/', $default) === 1;
    }
};
