<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGiftCardIdInUserRewardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_rewards', function (Blueprint $table) {
            Schema::table('user_rewards', function (Blueprint $table) {
                $table->unsignedBigInteger('gift_card_id')->nullable()->index()->after('reward_id');
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_rewards', function (Blueprint $table) {
            $table->foreign('reward_id')->references('id')->on('gift_cards')->onDelete('cascade');
        });
    }
}
