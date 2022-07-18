<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCampaignClicksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_clicks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brand_id')->index();
            $table->unsignedBigInteger('campaign_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->enum('is_clicked', ['0', '1'])->default('0');
            $table->timestamp('inserted_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->enum('is_completed', ['0', '1'])->default('0');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::table('campaign_clicks', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('brand_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_clicks');
    }
}
