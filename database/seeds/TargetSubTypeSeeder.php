<?php

use App\TargetSubtype;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TargetSubTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('target_subtypes')->truncate();
        TargetSubtype::insert([
            'target_id' => 1,
            'subtype_name' => 'Lead Generation',
            'minimum_cac' => 0.40
        ]);
        TargetSubtype::insert([
            'target_id' => 2,
            'subtype_name' => 'Watch Video 15 Seconds',
            'minimum_cac' => 0.03
        ]);
        TargetSubtype::insert([
            'target_id' => 2,
            'subtype_name' => 'Watch Video 30 Seconds',
            'minimum_cac' => 0.07
        ]);
        TargetSubtype::insert([
            'target_id' => 2,
            'subtype_name' => 'Watch Video 60 Seconds',
            'minimum_cac' => 0.10
        ]);
        TargetSubtype::insert([
            'target_id' => 2,
            'subtype_name' => 'Watch Video 90 Seconds',
            'minimum_cac' => 0.12
        ]);
        TargetSubtype::insert([
            'target_id' => 2,
            'subtype_name' => 'Watch Video 120 Seconds',
            'minimum_cac' => 0.15
        ]);
        TargetSubtype::insert([
            'target_id' => 3,
            'subtype_name' => 'Followed by Twitter',
            'minimum_cac' => 0.22
        ]);
        TargetSubtype::insert([
            'target_id' => 3,
            'subtype_name' => 'Followed by Instagram',
            'minimum_cac' => 0.20
        ]);
        TargetSubtype::insert([
            'target_id' => 3,
            'subtype_name' => 'Followed by Twitch',
            'minimum_cac' => 0.29
        ]);
        TargetSubtype::insert([
            'target_id' => 3,
            'subtype_name' => 'Followed by Youtube',
            'minimum_cac' => 0.19
        ]);
        TargetSubtype::insert([
            'target_id' => 3,
            'subtype_name' => 'Followed by Facebook',
            'minimum_cac' => 0.20
        ]);
        TargetSubtype::insert([
            'target_id' => 4,
            'subtype_name' => 'App Download',
            'minimum_cac' => 0.80
        ]);
        TargetSubtype::insert([
            'target_id' => 5,
            'subtype_name' => 'Website Clicks',
            'minimum_cac' => 0.05
        ]);
    }
}
