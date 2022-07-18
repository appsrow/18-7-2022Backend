<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUserCoinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumns('user_coins', array('user_reward_id'))) {
            Schema::table('user_coins', function (Blueprint $table) {
                $table->dropForeign(['user_reward_id']);
                $table->dropColumn('user_reward_id');
            });
        }

        if (Schema::hasColumns('user_coins', array('user_reward_id'))) {
            Schema::table('user_coins', function (Blueprint $table) {
                $table->dropColumn('user_reward_id');
            });
        }

        Schema::table('user_coins', function (Blueprint $table) {
            $table->unsignedBigInteger('user_reward_id')->index()->nullable()->after('user_id');
        });
        Schema::table('user_coins', function (Blueprint $table) {
            $table->foreign('user_reward_id')->references('id')->on('user_rewards')->onDelete('cascade');
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
