<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstagramFollowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('instagram_follows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('action_type_id')->index();
            $table->string('action_type_name', 200);
            $table->string('brand_instagram_account', 200);
            $table->string('user_instagram_account', 200);
            $table->integer('is_follower')->comment('-2 = Python script is not executed yet, -1 = Python script has thrown an error, 0 = not a follower, 1 = is a follower');
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
        Schema::dropIfExists('instagram_follows');
    }
}
