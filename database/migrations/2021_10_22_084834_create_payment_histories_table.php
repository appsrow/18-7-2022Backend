<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('campaign_id')->index()->nullable();
            $table->unsignedBigInteger('rewards_id')->index()->nullable();
            $table->unsignedBigInteger('invoice_id')->index()->nullable();
            $table->string('transaction_id', 100);
            $table->date('transaction_date');
            $table->string('transaction_type', 50);
            $table->string('transaction_status', 50);
            $table->string('payment_mode', 50)->nullable();
            $table->text('paypal_id')->nullable();
            $table->string('paypal_reference_number', 200)->nullable();
            $table->decimal('grand_total', 12, 2)->default(0.00);
            $table->text('paypal_response')->nullable();
            $table->integer('created_by')->default(0);
            $table->integer('updated_by')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        
        Schema::table('payment_histories', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('rewards_id')->references('id')->on('rewards')->onDelete('cascade');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_histories');
    }
}
