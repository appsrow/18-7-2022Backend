<?php

use App\Http\Controllers\API\V1\AdminDashboardController;
use App\Http\Controllers\API\V1\CampaignsController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

$router->get('/clear-cache', function () {
    Artisan::call('config:cache');
    Artisan::call('config:clear');
});

$router->get('users/confirm_email/{confirm_code}', 'LoginController@email_confirm');
$router->get('users/confirm_reset_token/{type}/{token}', 'LoginController@checkResetLink');
$router->get('users/logout', 'LoginController@logout');
$router->get('brands/logout', 'LoginController@logout');
$router->get('admin/logout', 'LoginController@logout');
$router->post('viewCampaignStatistics', 'MetricsController@ViewCampaignStatistics')->middleware("validjson");
$router->post('checkCampaignLink', 'MetricsController@checkCampaignLink')->middleware("validjson");
$router->post('checkCampaignLinkAndPassword', 'MetricsController@checkCampaignLinkAndPassword')->middleware("validjson");
$router->post('get_insta_access_token', 'InstagramController@GetInstagramAccessToken');
$router->post('get_insta_user_name', 'InstagramController@getInstaUserName');
$router->post('checkAmbassadorLink', 'AmbassadorsController@checkAmbassadorLink');
$router->post('checkAmbassadorLinkAndPassword', 'AmbassadorsController@checkAmbassadorLinkAndPassword');
$router->post('viewAmbassadorsStatistics', 'AmbassadorsController@viewStreamerPerformanceStatistics');
$router->get('viewTopStreamers', 'TwitchController@getTopStreamersData');

Route::middleware('cors')->group(function ($router) {
    /** START Without Authentication */
    $router->get('users/country', 'OtherController@load_country');
    $router->get('users/state/{id}', 'OtherController@load_state_by_country');
    $router->post('brands/resend_confirm_email', 'LoginController@resend_email_confirm');
    $router->any('webhook/paypal/payout/status', 'CampaignsController@PaymentPayoutStatus');

    Route::middleware('validjson')->group(function ($router) {
        // Users
        $router->post('users/login', 'LoginController@index');
        $router->post('users/register', 'UserController@Add');
        $router->post('users/social_login', 'LoginController@User_Google_Social_login');
        $router->post('users/resend_confirm_email', 'LoginController@resend_email_confirm');
        $router->post('users/forgotpassword', 'LoginController@postEmail');
        $router->post('users/resetpassword', 'LoginController@postReset');
        $router->post('users/send_verification_code', 'UserController@sendVerificationCode');
        $router->post('users/verifySmsCode', 'UserController@verifySmsCode');
        // Brands
        $router->post('brands/login', 'LoginController@login_brand');
        $router->post('brands/register', 'UserController@add_brand');
        $router->post('brands/changepassword', 'UserController@ChangePassword');
        $router->post('brands/forgotpassword', 'LoginController@postEmail');
        $router->post('brands/resetpassword',  'LoginController@postReset');
        // Admin
        $router->post('admin/login', 'LoginController@AdminLogin');
        $router->post('integrations/action', 'IntegrationController@postAction');
        $router->post('twitch/checkStreamerLink', 'TwitchController@checkReferralLink');
    });
    /** END Without Authentication */

    /** START With Authentication */
    Route::middleware(['auth:api'])->group(function () {
        Route::post('getAllQuestionsReport', 'CampaignsController@getFormQuestionsReport');
        // START Users
        Route::prefix('users/')->group(function ($router) {
            $router->post('profile', 'SettingController@UpdateUserProfile')->middleware("validjson");
            $router->post('get_all_rewards', 'OtherController@GetAllRewards');
            $router->post('changepassword', 'UserController@ChangePassword')->middleware("validjson");
            $router->post('get_user_profile_by_id', 'UserController@getByLoggedUser');
            $router->post('userwalletbalance', 'UserController@UserWalletBalalnce');
            $router->post('tasks', 'CampaignsController@GetAllTask');
            $router->post('creditoncampaigncomplete', 'CampaignsController@CreditOnCampaignComplete')->middleware("validjson");
            $router->post('redeemrewards', 'CampaignsController@RedeemRewards')->middleware("validjson");
            $router->post('alreadyredeemrewards', 'CampaignsController@AlreadyRedeemedRewards');
            $router->post('alreadycompletedtask', 'CampaignsController@AlreadyCompletedTask');
            $router->post('campaignclicked', 'CampaignsController@CampaignClicked')->middleware("validjson");
            $router->post('Twitter_Auth', 'CampaignsController@TwitterAuth');
            $router->post('TwitterSecondApi', 'CampaignsController@TwitterSecondApi');
            $router->post('twitch/promotedlist', 'TwitchController@GetPromotedStreamers');
            $router->get('twitch/profile/{id}', 'TwitchController@GetUserTwitchProfile');
            $router->post('twitch/search', 'TwitchController@SearchTwitchStreamers');
            $router->post('twitch/subscribe', 'TwitchController@UserTwitchSubscription');
            $router->get('redeemrewards/list', 'UserRewardsController@index');
            $router->post('redeem_gift_cards', 'UserRewardsController@redeemGiftCards');
            $router->post('get_gift_card_type_info', 'UserRewardsController@getGiftCardTypeInfo');
            $router->get('get_all_gift_card_types', 'UserRewardsController@getGiftCards');
            $router->post('get_all_form_questions', 'CampaignsController@getFormQuestions');
            $router->get('instagramFollows', 'InstagramController@getInstagramFollows');
            $router->put('instagramFollows', 'InstagramController@updateInstagramFollows');
            $router->post('instagramFollows', 'InstagramController@postInstagramFollows');
            $router->post('checkInstagramFollow', 'InstagramController@checkInstagramFollow');
            $router->post('addUserFormAnswers', 'CampaignsController@AddUserFormAnswers');
        });
        // END Users

        // START Brands
        Route::prefix('brands/')->group(function ($router) {
            $router->post('get_brand_profile_by_id', 'UserController@getByLoggedBrand');
            $router->post('brandspendoncampaign', 'UserController@BrandCampaignSpend');
            $router->post('profile', 'SettingController@UpdateBrandProfile')->middleware("validjson");
            $router->post('getcampaignposition', 'CampaignsController@GetCampaignPosition')->middleware("validjson");
            $router->post('GetMaxCacByCampaignType', 'CampaignsController@GetMaxCacByCampaignType')->middleware("validjson");
            $router->post('payment', 'BrandPaymentController@CampaignPaymentFromBrand')->middleware("validjson");
            $router->post('invoices', 'BrandPaymentController@InvoiceByUser');
            $router->get('downloadPDF/{id}', 'BrandPaymentController@downloadPDF');

            // START Campagins
            $router->post('createcampaign/leads', 'CampaignsController@LeadCampaign')->middleware("validjson");
            $router->post('createcampaign/video', 'CampaignsController@VideoCampaign')->middleware("validjson");
            $router->post('createcampaign/follow', 'CampaignsController@Follow_Campaign')->middleware("validjson");
            $router->post('createcampaign/click_website', 'CampaignsController@Website_Campaign')->middleware("validjson");
            $router->post('createcampaign/app_download', 'CampaignsController@App_Download_Campaign')->middleware("validjson");
            $router->post('createcampaign/minimumcac', 'CampaignsController@Target_Subtype')->middleware("validjson");
            $router->post('current_campaign', 'CampaignsController@GetCurrentCampaigns');
            $router->post('programmed_campaign', 'CampaignsController@GetProgrammedCampaigns');
            $router->post('finished_campaign', 'CampaignsController@GetFinishedCampaigns');
            $router->post('getcampaignbyid', 'CampaignsController@GetCampaignById')->middleware("validjson");
            $router->post('getprogrammedcampaignbyid', 'CampaignsController@GetProgrammedCampaignsById')->middleware("validjson");
            $router->post('editprogrammedcampaign', 'CampaignsController@EditProgrammedCampaigns')->middleware("validjson");
            $router->post('UpdateCampaignBudget', 'CampaignsController@UpdateCampaignBudget')->middleware("validjson");
            $router->post('startstoprunningcampaign', 'CampaignsController@StopRunningCampaign')->middleware("validjson");
            $router->post('campaignstatistics', 'CampaignsController@CampaignStatistics')->middleware("validjson");
            $router->post('getleadcampaign', 'CampaignsController@GetLeadCampaign');
            $router->get('getuserbyleadcampaign/{id}', 'CampaignsController@GetUserbyLeadCampaign');
            $router->post('createcampaign/questionForm', 'CampaignsController@QuestionsCampaign')->middleware("validjson");
            $router->get('getQuestionTypes', 'CampaignsController@load_question_types');
            $router->post('addCampaignFormQuestions', 'CampaignsController@AddCampaignFormQuestions')->middleware("validjson");
            $router->post('addCampaignForm', 'CampaignsController@addQuestionsForm')->middleware("validjson");
            $router->post('generateMetricsLink', 'MetricsController@generateRandomLink');
            $router->post('saveMetricsLink', 'MetricsController@saveMetricsLink');
            $router->post('getAllQuestionsReport', 'CampaignsController@getFormQuestionsReport');
            $router->post('getQuestionAnswers', 'CampaignsController@getQuestionAnswers');
            $router->get('downloadQuestionsCsv/{id}', 'CampaignsController@getFormCampaignDetailReport');
            // END Campagins
        });
        // END Brands

        // START Admin
        Route::prefix('admin/')->group(function ($router) {
            $router->post('getallusers', 'AdminController@GetAllUsers');
            $router->post('getallbrands', 'AdminController@GetAllBrands');
            $router->get('getuserbyid/{id}', 'AdminController@GetUserByid');
            $router->get('getbrandbyid/{id}', 'AdminController@GetBrandByid');
            $router->get('getcampaignbyid/{id}', 'AdminController@GetCampaignByid');
            $router->post('getallcampaign', 'AdminController@GetAllCampaign');
            $router->post('userstatus', 'AdminController@UserStatus')->middleware("validjson");
            $router->post('campaignapproval', 'AdminController@CampaignApprovalByAdmin')->middleware("validjson");
            $router->post('invoices', 'AdminController@GetAllInvoice')->middleware("cors");
            $router->get('DownloadInvoice/{id}', 'AdminController@DownloadInvoice');
            $router->get('GetPaymentHistoryByBrandId/{id}', 'AdminController@GetPaymentHistoryByBrandId');
            $router->get('dashboard/counts', 'AdminDashboardController@GetDashboardCounts');
            $router->get('dashboard/brands/{year}', 'AdminDashboardController@GetBrandRegisteredInYear');
            $router->post('dashboard/campaigns', 'AdminDashboardController@GetHighestPaidCampaign')->middleware("validjson");
            $router->post('report/campaign', 'AdminDashboardController@GetCampaignReport')->middleware("validjson");
            $router->post('redeemrewards/twitchsubscriptions', 'AdminController@getTwitchSubscriptions');
            $router->put('redeemrewards/twitchsubscriptions', 'AdminController@updateTwitchSubscriptions');
            $router->get('getAllGiftCards', 'GiftCardsController@GetAllGiftCardsList');
            $router->post('addGiftCard', 'GiftCardsController@addGiftCard');
            $router->post('changeGiftCardStatus', 'GiftCardsController@change_gift_card_status');
            $router->post('importMultipleGiftCardData', 'GiftCardsController@import');
            $router->get('get_gift_card_types', 'GiftCardsController@getGiftCardTypes');
            $router->post('getDashboardKpis', 'AdminDashboardController@getDashboardKpis');
        });
        // END Admin
        //Integrations
        Route::prefix('integrations/')->group(function ($router) {
            $router->post('checkaction', 'IntegrationController@checkAction');
        });
    });
    /** END With Authentication */
});
