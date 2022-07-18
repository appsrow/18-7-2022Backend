<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGiftCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();
            $table->string('card_code','250');
            $table->string('type', 50); // Ex: Amazon, twitch, playstation
            $table->decimal('amount', 12, 2)->nullable()->default('0');
            $table->integer('price')->comment('coins'); // 700 COINS
            $table->string('currency_code', 15)->nullable()->default('EUR');
            $table->string('user_photo', 150)->nullable();
            $table->enum('status', ['AVAILABLE', 'USED', 'EXPIRED', 'NOT_AVAILABLE'])->default('AVAILABLE');
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
        Schema::dropIfExists('gift_cards');
    }
}
