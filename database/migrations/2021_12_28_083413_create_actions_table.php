<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('actions', function (Blueprint $table) {
            $table->id();
			$table->integer('company_id')->nullable();
			$table->integer('user_id')->nullable();
			$table->integer('action_type_id')->nullable();
			$table->string('action_type_name', 50)->nullable();
			$table->string('campaign_name', 50)->nullable();
			$table->string('source', 50)->nullable();
			$table->string('medium', 50)->nullable();
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
        Schema::dropIfExists('actions');
    }
}
