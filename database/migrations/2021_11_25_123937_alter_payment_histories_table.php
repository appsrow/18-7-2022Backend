<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPaymentHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumns('payment_histories', array('transaction_id', 'transaction_type'))) {
            Schema::table('payment_histories', function (Blueprint $table) {
                $table->dropColumn('transaction_id');
                $table->dropColumn('transaction_type');
            });
        }

        Schema::table('payment_histories', function (Blueprint $table) {
            $table->string('transaction_id', 100)->after('invoice_id')->nullable();
            $table->string('transaction_type', 50)->after('transaction_date')->nullable();
            $table->text('paypal_request')->after('grand_total')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
