<?php

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::insert([
            'email' => 'admin@dropforcoin.com',
            'password' => Hash::make('admin@123'),
            'active' => 1,
            'user_type' => 3
        ]);
    }
}
