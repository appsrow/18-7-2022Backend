<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBrandWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('brand_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->foreignId('campaign_id')->default(0);
            $table->date('transaction_date')->nullable();
            $table->decimal('opening_balance', 12, 2)->default(0.00);
            $table->decimal('credit', 12, 2)->default(0.00);
            $table->decimal('debit', 12, 2)->default(0.00);
            $table->decimal('closing_balance', 12, 2)->default(0.00);
            $table->decimal('cac', 12, 2)->default(0.00);
            $table->text('comments')->nullable();
            $table->integer('created_by')->default(0);
            $table->integer('updated_by')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::table('brand_wallets', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('brand_wallets');
    }
}
