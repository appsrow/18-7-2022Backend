<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTwitterDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('twitter_data', function (Blueprint $table) {
            $table->id();
            $table->string('oauth_token', 200);
            $table->string('oauth_token_secret', 200);
            $table->string('user_id', 200);
            $table->string('target_screen_name', 200);
            $table->string('status', 200);
            $table->string('response', 200);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('twitter_data');
    }
}
