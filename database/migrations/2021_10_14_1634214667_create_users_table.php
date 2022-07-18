<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users', function (Blueprint $table) {
			$table->id();
			$table->string('first_name', 50)->nullable();
			$table->string('last_name', 50)->nullable();
			$table->date('dob')->nullable();
			$table->string('email', 50)->unique('users_email_unique');
			$table->string('password', 250)->nullable();
			$table->string('gender', 20)->nullable()->comment('1= Male 2= Female 3= Others');
			$table->string('city', 100)->nullable();
			$table->string('state', 100)->nullable();
			$table->string('country', 100)->nullable();
			$table->string('phone', 50)->nullable();
			$table->string('user_photo', 150)->nullable();
			$table->text('api_token')->nullable();
			$table->unsignedTinyInteger('active')->default(1);
			$table->enum('is_social_sign_in', ['0', '1'])->nullable()->default('0');
			$table->string('confirmation_code', 191)->nullable();
			$table->dateTime('confirmation_code_expired')->nullable();
			$table->boolean('confirmed')->default(0);
			$table->integer('created_by')->nullable();
			$table->integer('updated_by')->nullable();
			$table->enum('user_type', ['1', '2', '3'])->default(1)->comment('1= Brand(company) 2= User 3= Admin');
			$table->enum('is_deleted', ['0', '1'])->default('0');
			$table->timestamps();
			$table->timestamp('deleted_at')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('users');
	}
}
