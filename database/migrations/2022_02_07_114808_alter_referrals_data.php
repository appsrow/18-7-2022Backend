<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterReferralsData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('referrals_data', function (Blueprint $table) {
            $table->dropColumn('referral_id');
        });

        Schema::table('referrals_data', function (Blueprint $table) {
            $table->string('referral_id', 250)->after('user_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('referrals_data', function (Blueprint $table) {
            //
        });
    }
}
