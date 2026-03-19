<?php

namespace App\Console\Commands;

use App\Models\Driver;
use App\Models\Parents;
use App\Models\User;
use Illuminate\Console\Command;

class ReactivateMobileUser extends Command
{
    protected $signature = 'scb:reactivate-mobile-user
        {email : User email address}
        {--with-profile : Also reactivate linked parent/driver profile records}';

    protected $description = 'Reactivate a soft-deleted mobile user for parent/driver login.';

    public function handle(): int
    {
        $email = trim((string) $this->argument('email'));

        if ($email === '') {
            $this->error('Email is required.');
            return Command::FAILURE;
        }

        $user = User::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower($email)])
            ->first();

        if (! $user) {
            $this->error("User not found for email: {$email}");
            return Command::FAILURE;
        }

        $user->deleted = 0;
        if (property_exists($user, 'status') || array_key_exists('status', $user->getAttributes())) {
            $user->status = 1;
        }
        $user->save();

        $this->info("User reactivated: {$user->email} (id={$user->id})");

        if ((bool) $this->option('with-profile')) {
            $parentCount = Parents::query()
                ->where(function ($q) use ($user) {
                    $q->where('login_user_id', $user->id)
                        ->orWhere('user_id', $user->id);
                })
                ->update([
                    'deleted' => 0,
                    'status' => 1,
                ]);

            $driverCount = Driver::query()
                ->where(function ($q) use ($user) {
                    $q->where('login_user_id', $user->id)
                        ->orWhere('user_id', $user->id);
                })
                ->update([
                    'deleted' => 0,
                    'status' => 1,
                ]);

            $this->line("Parent profiles updated: {$parentCount}");
            $this->line("Driver profiles updated: {$driverCount}");
        }

        return Command::SUCCESS;
    }
}
