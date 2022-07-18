<?php

use App\Rewards;
use Illuminate\Database\Seeder;

class RewardTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Rewards::insert([
            'name' => 'twitch',
            'description' => 'Twitch subscription',
            'minimum_coins' => 664,
            'photo' => null,
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Rewards::insert([
            'name' => 'paypal',
            'description' => '€ 10 Paypal',
            'minimum_coins' => 500,
            'photo' => null,
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
