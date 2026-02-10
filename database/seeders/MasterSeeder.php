<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MasterSeeder extends Seeder
{
    public function run()
    {
        // ---------------- ROLES ----------------
        DB::table('roles')->insert([
            ['id' => 13, 'name' => 'Admin'],
            ['id' => 14, 'name' => 'Driver'],
            ['id' => 15, 'name' => 'Parent'],
        ]);

        // ---------------- PERMISSIONS ----------------

        // ---------------- USERS ----------------
       DB::table('users')->insert([
    [
        'role_id'    => 13,
        'first_name' => 'Admin',
        'last_name'  => 'User',
        'mobile'     => '9999999999',
        'photo'      => null,
        'name'       => 'Admin',
        'email'      => 'schoolcabservice@gmail.com',
        'password'   => Hash::make('123456'),
    ]
]);
    }
}
