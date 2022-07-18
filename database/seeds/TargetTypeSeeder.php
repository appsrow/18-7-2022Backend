<?php

use App\TargetType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TargetTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('target_types')->truncate();
        TargetType::insert([
            'target_type' => 'Lead',
            'category' => 'Appreciation'
        ]);
        TargetType::insert([
            'target_type' => 'Video Plays',
            'category' => 'Consideration'
        ]);
        TargetType::insert([
            'target_type' => 'Follow',
            'category' => 'Consideration'
        ]);
        TargetType::insert([
            'target_type' => 'App Downloads',
            'category' => 'Consideration'
        ]);
        TargetType::insert([
            'target_type' => 'Clicks on the Website',
            'category' => 'Consideration'
        ]);
        TargetType::insert([
            'target_type' => 'Reinteractions with the app',
            'category' => 'Conversion'
        ]);
        TargetType::insert([
            'target_type' => 'Questions form',
            'category' => 'Conversion'
        ]);
    }
}
