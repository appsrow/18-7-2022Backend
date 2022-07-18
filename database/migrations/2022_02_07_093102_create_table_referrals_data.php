<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableReferralsData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referrals_data', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->enum('user_type', ['1', '2', '3'])->default(2)->comment('1= Brand(company) 2= User 3= Admin');
            $table->integer('referral_id');
            $table->enum('referral_type', ['1','2'])->default(1)->comment('1= users 2=streamers');
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
        Schema::dropIfExists('table_referrals_data');
    }
}
