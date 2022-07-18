<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->enum('campaign_type', ['lead_target', 'video_plays', 'follow', 'apps_download', 'click_websites'])->default('lead_target');
            $table->text('campaign_type_name')->nullable();
            $table->string('campaign_name', 50)->nullable();
            $table->string('goal_of_campaign', 50)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('product_information');
            $table->decimal('cac', 12, 2)->nullable()->comment('Customer adquisition cost');
            $table->decimal('total_budget', 12, 2)->nullable();
            $table->decimal('coins', 12, 2)->nullable();
            $table->decimal('sub_total', 12, 2)->comment('Without tax');
            $table->decimal('tax_value', 12, 2)->comment('tax percentage');
            $table->string('user_target', 100)->nullable()->comment('The number of users can perform in this campaign');
            $table->string('campaign_image', 500)->nullable();
            $table->string('uploaded_video_url', 500)->nullable();
            $table->string('selected_social_media_name', 500)->nullable();
            $table->string('selected_social_media_url', 500)->nullable();
            $table->string('app_download_link', 500)->nullable();
            $table->string('website_url', 500)->nullable();
            $table->enum('is_start', ['0', '1'])->nullable()->default('1')->comment('Is campaign start or stop');
            $table->unsignedTinyInteger('active')->default(1);
            $table->enum('campaign_status', ['DRAFT', 'SUBMITTED', 'APPROVED'])->default('DRAFT');
            $table->enum('is_approved', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING')->comment('Is campaign approved by admin');
            $table->text('note')->nullable();
            $table->text('country')->nullable();
            $table->string('start_age', 20)->nullable();
            $table->string('end_age', 20)->nullable();
            $table->string('gender', 100)->nullable();
            $table->enum('is_budget_revised', ['YES', 'NO'])->default('NO');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->enum('is_deleted', ['0', '1'])->default('0');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaigns');
    }
}
