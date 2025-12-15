<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\WriterProfile;

class CreateWriterProfiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:writerprofiles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create WriterProfile entries for users without one';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = User::doesntHave('writerProfile')->get();

        foreach ($users as $user) {
            WriterProfile::create(['user_id' => $user->id]);
            $this->info("Created WriterProfile for user ID: {$user->id}");
        }

        $this->info('WriterProfile creation completed.');
    }
}
