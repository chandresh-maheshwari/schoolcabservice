<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $response = Http::post(
        'https://countriesnow.space/api/v0.1/countries/states',
        ['country' => 'India']
    );

    if ($response->successful()) {
        foreach ($response->json()['data']['states'] as $state) {
            State::firstOrCreate([
                'name' => $state['name']
            ]);
        }
    }
    }
}
