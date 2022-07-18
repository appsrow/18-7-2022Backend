<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_id', 200);
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('campaign_id')->index();
            $table->text('payment_id');
            $table->timestamp('invoice_date')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->decimal('cac', 12, 2)->default(0.00)->comment('Customer adquisition cost');
            $table->decimal('sub_total', 12, 2)->default(0.00);
            $table->decimal('discount', 12, 2)->default(0.00);
            $table->decimal('final_total', 12, 2)->default(0.00);
            $table->integer('tax_percentage')->nullable();
            $table->decimal('tax_value', 12, 2)->nullable();
            $table->decimal('grand_total', 12, 2)->default(0.00);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('invoices');
    }
}
