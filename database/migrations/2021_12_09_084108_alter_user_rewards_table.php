<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUserRewardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('user_rewards', function (Blueprint $table) {
            $table->enum('reward_status', ['PROCESSING', 'SUCCESS', 'DENIED', 'CANCELED', 'FAILED'])->default('PROCESSING')->after('streamer_twitch_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
