<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CreateProcedures extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS getRegisteredBrandInYear');
        DB::unprepared('DROP PROCEDURE IF EXISTS getDashboardCounts');
        DB::unprepared('DROP PROCEDURE IF EXISTS getCampaignReport');
        DB::unprepared('DROP PROCEDURE IF EXISTS getTopPaidCampaigns');

        $sqlProcedure1 = File::get("database/data/getDashboardCounts.sql");
        DB::unprepared($sqlProcedure1);
        $sqlProcedure2 = File::get("database/data/getRegisteredBrandInYear.sql");
        DB::unprepared($sqlProcedure2);
        $sqlProcedure3 = File::get("database/data/getCampaignReport.sql");
        DB::unprepared($sqlProcedure3);
        $sqlProcedure4 = File::get("database/data/getTopPaidCampaigns.sql");
        DB::unprepared($sqlProcedure4);



    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
