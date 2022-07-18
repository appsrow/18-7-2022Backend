<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserRewardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('reward_id')->index();
            $table->decimal('redeem_coins', 12, 2)->default(0.00);
            $table->string('description', 100)->nullable();
            $table->unsignedBigInteger('payment_history_id')->nullable();
            $table->string('user_twitch_id', 50)->nullable();
            $table->string('streamer_twitch_id', 50)->nullable();
            $table->timestamps();
        });

        Schema::table('user_rewards', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reward_id')->references('id')->on('rewards')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_rewards');
    }
}
