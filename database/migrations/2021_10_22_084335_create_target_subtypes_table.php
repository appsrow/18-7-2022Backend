<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTargetSubtypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('target_subtypes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('target_id')->index();
            $table->string('subtype_name', 100)->nullable();
            $table->decimal('minimum_cac', 6, 2)->nullable();
        });

        Schema::table('target_subtypes', function (Blueprint $table) {
            $table->foreign('target_id')->references('id')->on('target_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('target_subtypes');
    }
}
