<?php

namespace App\Http\Controllers\API\V1;

use Illuminate\Http\Request;
use App\User;
use App\TargetSubtype;
use App\TargetType;
use App\Company;
use App\Campaign;
use App\UserCoins;
use App\Rewards;
use App\BrandWallet;
use App\BrandWalletBalance;
use App\UserCoinsBalances;
use App\CampaignClick;
use App\Action;
use App\CampaignFormData;
use App\CampaignFormQuestion;
use Illuminate\Support\Facades\Auth;
use App\Helpers\GeneralHelper;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use DateTime;
use App\PaymentHistory;
use App\Invoice;
use Illuminate\Support\Facades\URL;
use PDF;
use Illuminate\Support\Facades\Lang;
use App\UserRewards;
use App\Log;
use App\QuestionType;
use App\TwitterFollows;
use App\UserFormAnswer;
use Exception;

class CampaignsController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Campaigns Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles all campaign related functionalities.
    */

    /**
     * @LeadCampaign - This API is used for add new campaign (Type: lead_target).
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function LeadCampaign(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "LeadCampaign";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $user_id = Auth::id();
            $ids = User::where('user_type', 1)->where('id', $user_id)->first();
            if (!empty($ids)) {
                $userDetails = Company::where('user_id', $user_id)->first();
                if (!empty($userDetails->id)) {
                    $validator =  Validator::make($requestData, [
                        'campaign_name' => 'required',
                        'start_date' => 'required',
                        'end_date' => 'required',
                        'product_information' => 'required',
                        'cac' => 'required',
                        'sub_total' => 'required',
                        'tax_value' => 'required',
                        'total_budget' => 'required',
                        'coins' => 'required',
                        'user_target' => 'required',
                        'campaign_image' => 'required'
                    ]);
                    if ($validator->fails()) {
                        $error = $validator->errors()->first();
                        return $this->sendError($error, null, 400);
                    }
                    $goal_of_campaign = "";
                    $campaign_type_name = "";
                    $campaign_name = $requestData['campaign_name'];
                    $campaign_type = $requestData['campaign_type'];
                    if (!empty($requestData['goal_of_campaign'])) {
                        $goal_of_campaign = $requestData['goal_of_campaign'];
                    }
                    $start_date = $requestData['start_date'];
                    $end_date = $requestData['end_date'];
                    $product_information = $requestData['product_information'];
                    $cac = $requestData['cac'];
                    $sub_total = $requestData['sub_total'];
                    $tax_value = $requestData['tax_value'];
                    $total_budget = $requestData['total_budget'];
                    $coins = $requestData['coins'];
                    $user_target = $requestData['user_target'];
                    $campaign_image = $requestData['campaign_image'];
                    if (!empty($requestData['country'])) {
                        $country = $requestData['country'];
                    }
                    if (!empty($requestData['start_age'])) {
                        $start_age = $requestData['start_age'];
                    }
                    if (!empty($requestData['end_age'])) {
                        $end_age = $requestData['end_age'];
                    }
                    if (!empty($requestData['gender'])) {
                        $gender = $requestData['gender'];
                    }
                    if (!empty($requestData['campaign_type_name'])) {
                        $campaign_type_name = $requestData['campaign_type_name'];
                    }
                    $campaign = new Campaign;
                    $campaign->company_id = $userDetails->id;
                    $campaign->campaign_name = $campaign_name;
                    $campaign->campaign_type = $campaign_type;
                    if (!empty($campaign_type_name)) {
                        $campaign->campaign_type_name = $campaign_type_name;
                    }
                    if (!empty($goal_of_campaign)) {
                        $campaign->goal_of_campaign = $goal_of_campaign;
                    }
                    $campaign->start_date = date("Y-m-d", strtotime($start_date));
                    $campaign->end_date = date("Y-m-d", strtotime($end_date));
                    $campaign->product_information = $product_information;
                    $campaign->cac = $cac;
                    $campaign->sub_total = $sub_total;
                    $campaign->tax_value = $tax_value;
                    $campaign->total_budget = $total_budget;

                    //calling CalculateCoinUserTarget function
                    if (!empty($sub_total) and !empty($cac)) {
                        $this->CalculateCoinUserTarget($sub_total, $cac, $campaign);
                    }

                    if (!empty($country)) {
                        $campaign->country = $country;
                    }
                    if (!empty($start_age)) {
                        $campaign->start_age = $start_age;
                    }
                    if (!empty($end_age)) {
                        $campaign->end_age = $end_age;
                    }
                    if (!empty($gender)) {
                        $campaign->gender = $gender;
                    }
                    if (!empty($campaign_image)) {
                        $this->image_upload($campaign_image, $campaign);
                    }
                    $campaign->save();
                    if (!empty($campaign)) {
                        $Campaign_Details = Campaign::find($campaign->id);
                        if (!empty($Campaign_Details->campaign_image)) {
                            $Original = URL::to('/') . '/' . 'uploads/user_files/' . $Campaign_Details->campaign_image;
                            $Campaign_Details['campaign_image'] = $Original;
                        }
                        return $this->sendResponse($Campaign_Details, Lang::get("common.success", array(), $this->selected_language), 200);
                    }
                } else {
                    return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 201);
                }
            } else {
                return $this->sendError(Lang::get("common.unauthorized", array(), $this->selected_language), json_decode("{}"), 401);
            }
        }
    }

    /**
     * @image_upload - image upload
     * 
     * @param \Illuminate\Http\Request $campaign_image
     * @param {Campaign Object} $campaign
     * @return \Illuminate\Http\JsonResponse
     */
    public function image_upload($campaign_image, $campaign)
    {

        if ($campaign_image) {
            $folderPath = 'uploads' . DIRECTORY_SEPARATOR . 'user_files' . DIRECTORY_SEPARATOR;
            $destinationPath = GeneralHelper::public_path($folderPath);
            $image_parts = explode(";base64,", $campaign_image);
            $image_partss = explode("data:image/", $campaign_image);
            if (!empty($image_partss[1])) {
                $image_type_aux = explode("image/", $image_parts[0]);
                $image_type = $image_type_aux[1];
                if (($image_type == "png") || ($image_type == "jpeg") || ($image_type == "jpg")) {
                    $size_in_bytes = (int) (strlen(rtrim($campaign_image, '=')) * 3 / 4);
                    $size_in_kb    = $size_in_bytes / 1024;
                    $size_in_mb    = $size_in_kb / 1024;
                    $newfaltvalue = floor($size_in_mb);
                    if ($newfaltvalue > 10) {
                        return $this->sendError(Lang::get("campaign.file_upload_limit_error", array(), $this->selected_language), json_decode("{}"), 400);
                    }
                    $image_base64 = base64_decode($image_parts[1]);
                    $uniqid = uniqid();
                    $file =  $destinationPath . $uniqid . '.' . $image_type;
                    file_put_contents($file, $image_base64);
                    $campaign->campaign_image = $uniqid . '.' . $image_type;
                } else {
                    return $this->sendError(Lang::get("campaign.file_type_error", array(), $this->selected_language), json_decode("{}"), 400);
                }
            }
        }
    }

    /**
     * @video_upload - video upload
     * 
     * @param \Illuminate\Http\Request $uploaded_video_url
     * @param {Campaign Object} $uploaded_video_url
     * @return \Illuminate\Http\JsonResponse
     */
    public function video_upload($uploaded_video_url, $campaign)
    {

        if ($uploaded_video_url) {
            $folderPath = 'uploads' . DIRECTORY_SEPARATOR . 'user_files' . DIRECTORY_SEPARATOR;
            $destinationPath = GeneralHelper::public_path($folderPath);
            $video_parts = explode(";base64,", $uploaded_video_url);
            $video_partss = explode("data:video/", $uploaded_video_url);
            if (!empty($video_partss[1])) {
                $video_type_aux = explode("video/", $video_parts[0]);
                $video_type = $video_type_aux[1];
                if (($video_type == "mp4") || ($video_type == "mpeg4") || ($video_type == "mpeg") || ($video_type == "mpeg2")) {
                    $size_in_bytes = (int) (strlen(rtrim($uploaded_video_url, '=')) * 3 / 4);
                    $size_in_kb    = $size_in_bytes / 1024;
                    $size_in_mb    = $size_in_kb / 1024;
                    $newfaltvalue = floor($size_in_mb);
                    if ($newfaltvalue > 450) {
                        return $this->sendError(Lang::get("campaign.video_upload_limit_error", array(), $this->selected_language), json_decode("{}"), 400);
                    }
                    $video_base64 = base64_decode($video_parts[1]);
                    $uniqid = uniqid();
                    $file =  $destinationPath . $uniqid . '.' . $video_type;
                    file_put_contents($file, $video_base64);
                    $campaign->uploaded_video_url = $uniqid . '.' . $video_type;
                } else {
                    return $this->sendError(Lang::get("campaign.video_type_error", array(), $this->selected_language), json_decode("{}"), 400);
                }
            }
        }
    }
    /**
     * @VideoCampaign - This API is used for add new campaign (Type: video_plays).
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function VideoCampaign(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "VideoCampaign";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $user_id = Auth::id();
            $ids = User::where('user_type', 1)->where('id', $user_id)->first();
            if (!empty($ids)) {
                $userDetails = Company::where('user_id', $user_id)->first();
                $validator =  Validator::make($requestData, [
                    'campaign_name' => 'required',
                    'start_date' => 'required',
                    'end_date' => 'required',
                    'product_information' => 'required',
                    'cac' => 'required',
                    'sub_total' => 'required',
                    'tax_value' => 'required',
                    'total_budget' => 'required',
                    'coins' => 'required',
                    'user_target' => 'required',
                    'campaign_image' => 'required',
                    // 'uploaded_video_url' => 'required',
                ]);
                if ($validator->fails()) {
                    $error = $validator->errors()->first();
                    return $this->sendError($error, null, 400);
                }
                $goal_of_campaign = "";
                $campaign_type_name = "";
                $campaign_name = $requestData['campaign_name'];
                if (!empty($requestData['goal_of_campaign'])) {
                    $goal_of_campaign = $requestData['goal_of_campaign'];
                }
                $start_date = $requestData['start_date'];
                $end_date = $requestData['end_date'];
                $product_information = $requestData['product_information'];
                $cac = $requestData['cac'];
                $sub_total = $requestData['sub_total'];
                $tax_value = $requestData['tax_value'];
                $total_budget = $requestData['total_budget'];
                $coins = $requestData['coins'];
                $user_target = $requestData['user_target'];
                $campaign_image = $requestData['campaign_image'];
                if (!empty($requestData['country'])) {
                    $country = $requestData['country'];
                }
                if (!empty($requestData['start_age'])) {
                    $start_age = $requestData['start_age'];
                }
                if (!empty($requestData['end_age'])) {
                    $end_age = $requestData['end_age'];
                }
                if (!empty($requestData['gender'])) {
                    $gender = $requestData['gender'];
                }
                $campaign_type = $requestData['campaign_type'];
                $uploaded_video_url = $requestData['uploaded_video_url'];
                if (!empty($requestData['campaign_type_name'])) {
                    $campaign_type_name = $requestData['campaign_type_name'];
                }
                if (!empty($requestData['youtube_video_url'])) {
                    $youtube_video_url = $requestData['youtube_video_url'];
                }
                $campaign = new Campaign;
                $campaign->company_id = $userDetails->id;
                $campaign->campaign_type = $campaign_type;
                if (!empty($campaign_type_name)) {
                    $campaign->campaign_type_name = $campaign_type_name;
                }
                $campaign->campaign_name = $campaign_name;
                if (!empty($goal_of_campaign)) {
                    $campaign->goal_of_campaign = $goal_of_campaign;
                }
                $campaign->start_date = date("Y-m-d", strtotime($start_date));
                $campaign->end_date = date("Y-m-d", strtotime($end_date));
                $campaign->product_information = $product_information;
                $campaign->cac = $cac;
                $campaign->sub_total = $sub_total;
                $campaign->tax_value = $tax_value;
                $campaign->total_budget = $total_budget;
                if (!empty($country)) {
                    $campaign->country = $country;
                }
                if (!empty($start_age)) {
                    $campaign->start_age = $start_age;
                }
                if (!empty($end_age)) {
                    $campaign->end_age = $end_age;
                }
                if (!empty($gender)) {
                    $campaign->gender = $gender;
                }
                //calling CalculateCoinUserTarget function
                if (!empty($sub_total) and !empty($cac)) {
                    $this->CalculateCoinUserTarget($sub_total, $cac, $campaign);
                }
                //calling image upload function
                if (!empty($campaign_image)) {
                    $this->image_upload($campaign_image, $campaign);
                }
                if (!empty($uploaded_video_url)) {
                    $this->video_upload($uploaded_video_url, $campaign);
                }
                if (!empty($youtube_video_url)) {
                    $campaign->youtube_video_url = $youtube_video_url;
                }
                $campaign->save();
                if (!empty($campaign)) {
                    $Campaign_Details = Campaign::find($campaign->id);
                    if (!empty($Campaign_Details->campaign_image)) {
                        $Original = URL::to('/') . '/' . 'uploads/user_files/' . $Campaign_Details->campaign_image;
                        $Campaign_Details['campaign_image'] = $Original;
                    }
                    if (!empty($Campaign_Details->uploaded_video_url)) {
                        $Original = URL::to('/') . '/' . 'uploads/user_files/' . $Campaign_Details->uploaded_video_url;
                        $Campaign_Details['uploaded_video_url'] = $Original;
                    }
                    return $this->sendResponse($Campaign_Details, Lang::get("common.success", array(), $this->selected_language), 200);
                }
            } else {
                return $this->sendError(Lang::get("common.unauthorized", array(), $this->selected_language), json_decode("{}"), 401);
            }
        } else {
            return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), json_decode("{}"), 400);
        }
    }
    /**
     * @Follow_Campaign - This API is used for add new campaign (Type: follow). Social media used are: Facebook, INstagram, Twitter, Youtube, Twitch.
     * 
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function Follow_Campaign(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "Follow_Campaign";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $user_id = Auth::id();
            $ids = User::where('user_type', 1)->where('id', $user_id)->first();
            if (!empty($ids)) {
                $userDetails = Company::where('user_id', $user_id)->first();
                $validator =  Validator::make($requestData, [
                    'campaign_name' => 'required',
                    'start_date' => 'required',
                    'end_date' => 'required',
                    'product_information' => 'required',
                    'cac' => 'required',
                    'sub_total' => 'required',
                    'tax_value' => 'required',
                    'total_budget' => 'required',
                    'coins' => 'required',
                    'user_target' => 'required',
                    'campaign_image' => 'required',
                    'selected_social_media_name' => 'required',
                    'selected_social_media_url' => 'required'
                ]);
                if ($validator->fails()) {
                    $error = $validator->errors()->first();
                    return $this->sendError($error, null, 400);
                }
                $goal_of_campaign = "";
                $campaign_type_name = "";
                $campaign_name = $requestData['campaign_name'];
                if (!empty($requestData['goal_of_campaign'])) {
                    $goal_of_campaign = $requestData['goal_of_campaign'];
                }
                $start_date = $requestData['start_date'];
                $end_date = $requestData['end_date'];
                $product_information = $requestData['product_information'];
                $cac = $requestData['cac'];
                $sub_total = $requestData['sub_total'];
                $tax_value = $requestData['tax_value'];
                $total_budget = $requestData['total_budget'];
                $coins = $requestData['coins'];
                $user_target = $requestData['user_target'];
                $campaign_image = $requestData['campaign_image'];
                if (!empty($requestData['country'])) {
                    $country = $requestData['country'];
                }
                if (!empty($requestData['start_age'])) {
                    $start_age = $requestData['start_age'];
                }
                if (!empty($requestData['end_age'])) {
                    $end_age = $requestData['end_age'];
                }
                if (!empty($requestData['gender'])) {
                    $gender = $requestData['gender'];
                }
                $campaign_type = $requestData['campaign_type'];
                $selected_social_media_name = $requestData['selected_social_media_name'];
                $selected_social_media_url = $requestData['selected_social_media_url'];
                if (!empty($requestData['campaign_type_name'])) {
                    $campaign_type_name = $requestData['campaign_type_name'];
                }
                $campaign = new Campaign;
                $campaign->company_id = $userDetails->id;
                $campaign->campaign_type = $campaign_type;
                if (!empty($campaign_type_name)) {
                    $campaign->campaign_type_name = $campaign_type_name;
                }
                $campaign->campaign_name = $campaign_name;
                if (!empty($goal_of_campaign)) {
                    $campaign->goal_of_campaign = $goal_of_campaign;
                }
                $campaign->start_date = date("Y-m-d", strtotime($start_date));
                $campaign->end_date = date("Y-m-d", strtotime($end_date));
                $campaign->product_information = $product_information;
                $campaign->cac = $cac;
                $campaign->sub_total = $sub_total;
                $campaign->tax_value = $tax_value;
                $campaign->total_budget = $total_budget;
                if (!empty($country)) {
                    $campaign->country = $country;
                }
                if (!empty($start_age)) {
                    $campaign->start_age = $start_age;
                }
                if (!empty($end_age)) {
                    $campaign->end_age = $end_age;
                }
                if (!empty($gender)) {
                    $campaign->gender = $gender;
                }
                $campaign->selected_social_media_url = $selected_social_media_url;
                $campaign->selected_social_media_name = $selected_social_media_name;
                //calling CalculateCoinUserTarget function
                if (!empty($sub_total) and !empty($cac)) {
                    $this->CalculateCoinUserTarget($sub_total, $cac, $campaign);
                }
                //calling image upload function
                if (!empty($campaign_image)) {
                    $this->image_upload($campaign_image, $campaign);
                }
                $campaign->save();
                if (!empty($campaign)) {
                    $Campaign_Details = Campaign::find($campaign->id);
                    if (!empty($Campaign_Details->campaign_image)) {
                        $Original = URL::to('/') . '/' . 'uploads/user_files/' . $Campaign_Details->campaign_image;
                        $Campaign_Details['campaign_image'] = $Original;
                    }
                    return $this->sendResponse($Campaign_Details, Lang::get("common.success", array(), $this->selected_language), 200);
                }
            } else {
                return $this->sendError(Lang::get("common.unauthorized", array(), $this->selected_language), json_decode("{}"), 401);
            }
        } else {
            return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), json_decode("{}"), 400);
        }
    }
    /**
     * @Follow_Campaign - This API is used for add new campaign (Type: app_downloads).
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function App_Download_Campaign(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "App_Download_Campaign";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $user_id = Auth::id();
            $ids = User::where('user_type', 1)->where('id', $user_id)->first();
            if (!empty($ids)) {
                $userDetails = Company::where('user_id', $user_id)->first();
                $validator =  Validator::make($requestData, [
                    'campaign_name' => 'required',
                    'start_date' => 'required',
                    'end_date' => 'required',
                    'product_information' => 'required',
                    'cac' => 'required',
                    'sub_total' => 'required',
                    'tax_value' => 'required',
                    'total_budget' => 'required',
                    'coins' => 'required',
                    'user_target' => 'required',
                    'campaign_image' => 'required',
                    'app_download_link' => 'required'
                ]);
                if ($validator->fails()) {
                    $error = $validator->errors()->first();
                    return $this->sendError($error, null, 400);
                }
                $goal_of_campaign = "";
                $campaign_type_name = "";
                $campaign_name = $requestData['campaign_name'];
                if (!empty($requestData['goal_of_campaign'])) {
                    $goal_of_campaign = $requestData['goal_of_campaign'];
                }
                $start_date = $requestData['start_date'];
                $end_date = $requestData['end_date'];
                $product_information = $requestData['product_information'];
                $cac = $requestData['cac'];
                $sub_total = $requestData['sub_total'];
                $tax_value = $requestData['tax_value'];
                $total_budget = $requestData['total_budget'];
                $coins = $requestData['coins'];
                $user_target = $requestData['user_target'];
                $campaign_image = $requestData['campaign_image'];
                if (!empty($requestData['country'])) {
                    $country = $requestData['country'];
                }
                if (!empty($requestData['start_age'])) {
                    $start_age = $requestData['start_age'];
                }
                if (!empty($requestData['end_age'])) {
                    $end_age = $requestData['end_age'];
                }
                if (!empty($requestData['gender'])) {
                    $gender = $requestData['gender'];
                }
                $campaign_type = $requestData['campaign_type'];
                $app_download_link = $requestData['app_download_link'];
                if (!empty($requestData['campaign_type_name'])) {
                    $campaign_type_name = $requestData['campaign_type_name'];
                }
                $campaign = new Campaign;
                $campaign->company_id = $userDetails->id;
                $campaign->campaign_type = $campaign_type;
                $campaign->campaign_name = $campaign_name;
                if (!empty($goal_of_campaign)) {
                    $campaign->goal_of_campaign = $goal_of_campaign;
                }
                $campaign->start_date = date("Y-m-d", strtotime($start_date));
                $campaign->end_date = date("Y-m-d", strtotime($end_date));
                $campaign->product_information = $product_information;
                $campaign->cac = $cac;
                $campaign->sub_total = $sub_total;
                $campaign->tax_value = $tax_value;
                $campaign->total_budget = $total_budget;
                if (!empty($country)) {
                    $campaign->country = $country;
                }
                if (!empty($start_age)) {
                    $campaign->start_age = $start_age;
                }
                if (!empty($end_age)) {
                    $campaign->end_age = $end_age;
                }
                if (!empty($gender)) {
                    $campaign->gender = $gender;
                }
                if (!empty($campaign_type_name)) {
                    $campaign->campaign_type_name = $campaign_type_name;
                }
                $campaign->app_download_link = $app_download_link;
                //calling CalculateCoinUserTarget function
                if (!empty($sub_total) and !empty($cac)) {
                    $this->CalculateCoinUserTarget($sub_total, $cac, $campaign);
                }
                //calling image upload function
                if (!empty($campaign_image)) {
                    $this->image_upload($campaign_image, $campaign);
                }
                $campaign->save();
                if (!empty($campaign)) {
                    $Campaign_Details = Campaign::find($campaign->id);
                    if (!empty($Campaign_Details->campaign_image)) {
                        $Original = URL::to('/') . '/' . 'uploads/user_files/' . $Campaign_Details->campaign_image;
                        $Campaign_Details['campaign_image'] = $Original;
                    }
                    return $this->sendResponse($Campaign_Details, Lang::get("common.success", array(), $this->selected_language), 200);
                }
            } else {
                return $this->sendError(Lang::get("common.unauthorized", array(), $this->selected_language), json_decode("{}"), 401);
            }
        } else {
            return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), json_decode("{}"), 400);
        }
    }
    /**
     * @Website_Campaign - This API is used for add new campaign (Type: click_websites).
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function Website_Campaign(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "Website_Campaign";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $user_id = Auth::id();
            $ids = User::where('user_type', 1)->where('id', $user_id)->first();
            if (!empty($ids)) {
                $userDetails = Company::where('user_id', $user_id)->first();
                $validator =  Validator::make($requestData, [
                    'campaign_name' => 'required',
                    'start_date' => 'required',
                    'end_date' => 'required',
                    'product_information' => 'required',
                    'cac' => 'required',
                    'sub_total' => 'required',
                    'tax_value' => 'required',
                    'total_budget' => 'required',
                    'coins' => 'required',
                    'user_target' => 'required',
                    'campaign_image' => 'required',
                    'website_url' => 'required'
                ]);
                if ($validator->fails()) {
                    $error = $validator->errors()->first();
                    return $this->sendError($error, null, 400);
                }
                $goal_of_campaign = "";
                $campaign_type_name = "";
                $campaign_name = $requestData['campaign_name'];
                if (!empty($requestData['goal_of_campaign'])) {
                    $goal_of_campaign = $requestData['goal_of_campaign'];
                }
                $start_date = $requestData['start_date'];
                $end_date = $requestData['end_date'];
                $product_information = $requestData['product_information'];
                $cac = $requestData['cac'];
                $sub_total = $requestData['sub_total'];
                $tax_value = $requestData['tax_value'];
                $total_budget = $requestData['total_budget'];
                $coins = $requestData['coins'];
                $user_target = $requestData['user_target'];
                $campaign_image = $requestData['campaign_image'];
                if (!empty($requestData['country'])) {
                    $country = $requestData['country'];
                }
                if (!empty($requestData['start_age'])) {
                    $start_age = $requestData['start_age'];
                }
                if (!empty($requestData['end_age'])) {
                    $end_age = $requestData['end_age'];
                }
                if (!empty($requestData['gender'])) {
                    $gender = $requestData['gender'];
                }
                $campaign_type = $requestData['campaign_type'];
                $website_url = $requestData['website_url'];
                if (!empty($requestData['campaign_type_name'])) {
                    $campaign_type_name = $requestData['campaign_type_name'];
                }
                $campaign = new Campaign;
                $campaign->company_id = $userDetails->id;
                $campaign->campaign_type = $campaign_type;
                if (!empty($campaign_type_name)) {
                    $campaign->campaign_type_name = $campaign_type_name;
                }
                $campaign->campaign_name = $campaign_name;
                if (!empty($goal_of_campaign)) {
                    $campaign->goal_of_campaign = $goal_of_campaign;
                }
                $campaign->start_date = date("Y-m-d", strtotime($start_date));
                $campaign->end_date = date("Y-m-d", strtotime($end_date));
                $campaign->product_information = $product_information;
                $campaign->cac = $cac;
                $campaign->sub_total = $sub_total;
                $campaign->tax_value = $tax_value;
                $campaign->total_budget = $total_budget;
                if (!empty($country)) {
                    $campaign->country = $country;
                }
                if (!empty($start_age)) {
                    $campaign->start_age = $start_age;
                }
                if (!empty($end_age)) {
                    $campaign->end_age = $end_age;
                }
                if (!empty($gender)) {
                    $campaign->gender = $gender;
                }
                $campaign->website_url = $website_url;
                //calling CalculateCoinUserTarget function
                if (!empty($sub_total) and !empty($cac)) {
                    $this->CalculateCoinUserTarget($sub_total, $cac, $campaign);
                }
                //calling image upload function
                if (!empty($campaign_image)) {
                    $this->image_upload($campaign_image, $campaign);
                }
                $campaign->save();
                if (!empty($campaign)) {
                    $Campaign_Details = Campaign::find($campaign->id);
                    if (!empty($Campaign_Details->campaign_image)) {
                        $Original = URL::to('/') . '/' . 'uploads/user_files/' . $Campaign_Details->campaign_image;
                        $Campaign_Details['campaign_image'] = $Original;
                    }
                    return $this->sendResponse($Campaign_Details, Lang::get("common.success", array(), $this->selected_language), 200);
                }
            } else {
                return $this->sendError(Lang::get("common.unauthorized", array(), $this->selected_language), json_decode("{}"), 401);
            }
        } else {
            return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), json_decode("{}"), 400);
        }
    }
    /**
     * @getAge - Get Age From Date
     * 
     * @param \Illuminate\Http\Request $date
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAge($date)
    {
        $from = new DateTime($date);
        $to   = new DateTime('today');
        return $from->diff($to)->y;
    }
    /**
     * @encrypt_decrypt - encrypting and decrypting campaign_id
     * 
     * @param \Illuminate\Http\Request $string
     * @param {encrypt,decrypt} $action
     * @return \Illuminate\Http\JsonResponse
     */
    public function encrypt_decrypt($string, $action = 'encrypt')
    {
        $encrypt_method = "AES-256-CBC";
        $secret_key = 'AA74CDCC2BBRT935136HH7B63C27'; // user define private key
        $secret_iv = '5fgf5HJ5g27'; // user define secret key
        $key = hash('sha256', $secret_key);
        $iv = substr(hash('sha256', $secret_iv), 0, 16); // sha256 is hash_hmac_algo
        if ($action == 'encrypt') {
            $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
            $output = base64_encode($output);
            $output .= "#" . Str::random(64);
        } else if ($action == 'decrypt') {
            $string = explode('#', $string);
            $output = openssl_decrypt(base64_decode($string[0]), $encrypt_method, $key, 0, $iv);
        }
        return $output;
    }
    /**
     * @GetAllTask - This API is used for get all running campaigns.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetAllTask(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetAllTask";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $user_id = Auth::id();
        $ids = User::where('user_type', 2)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $approval = "APPROVED";
            $admin_is_approved = "APPROVED";
            $date = $ids->dob;
            $age = $this->getAge($date);
            $add_query = "";
            if (!empty($ids->gender)) {
                $gender = $ids->gender;
            }
            if (!empty($ids->country)) {
                $country = $ids->country;
            }
            if (!empty($age)) {
                $add_query .= " AND ((" . $age . " between start_age and end_age) OR (start_age IS NULL AND end_age IS NULL))";
            }
            if (!empty($country)) {
                $add_query .= " AND (find_in_set('" . $country . "',country) OR (country IS NULL))";
            }
            if (!empty($gender)) {
                $add_query .= " AND (find_in_set(" . $gender . ",gender) OR (gender IS NULL))";
            }
            $campaignss = DB::select(DB::raw('SELECT *
                        FROM campaigns 
                        Where start_date <= CURDATE()
                        AND end_date >= CURDATE()
                        AND campaign_status = "' . $approval . '"
                        AND is_start = "1"
                        AND active = "1"
                        AND is_approved = "' . $admin_is_approved . '"
                        ' . $add_query . ' ORDER BY coins DESC'));
            $total_campaign = count($campaignss);
            $UserCoins = UserCoins::all()
                ->where('user_id', $user_id)
                ->where('campaign_id', '!=', null);
            $skip_campaigns = [];
            foreach ($UserCoins as $UserCoin) {
                $skip_campaigns[] = $UserCoin->campaign_id;
            }
            if ($total_campaign) {
                $data = [];
                foreach ($campaignss as $campaign) {
                    $closing_datas =  BrandWallet::select(
                        'brand_wallets.campaign_id',
                        'brand_wallets.closing_balance',
                        'campaigns.coins'
                    )
                        ->join('campaigns', 'brand_wallets.campaign_id', '=', 'campaigns.id')
                        ->where('campaign_id', $campaign->id)
                        ->latest('brand_wallets.id')->limit(1)->get();
                    // Check if campaign has balance skip campaigns and latest balance check of brand wallet
                    foreach ($closing_datas as $closing_data) {
                        $euro = round((($closing_data->coins * 0.006) * 2), 2);
                        if ($euro >= $closing_data->closing_balance) {
                            array_push($skip_campaigns, $closing_data->campaign_id);
                        }
                    }

                    $skip_campaign = array_unique($skip_campaigns);
                    if (!empty($skip_campaign) and in_array($campaign->id, $skip_campaign)) {
                        continue;
                    } else {
                        if (!empty($campaign->campaign_image)) {
                            $Original = URL::to('/') . '/' . 'uploads/user_files/' . $campaign->campaign_image;
                            $campaign->campaign_image = $Original;
                        }
                        if (!empty($campaign->uploaded_video_url)) {
                            $Original_video = URL::to('/') . '/' . 'uploads/user_files/' . $campaign->uploaded_video_url;
                            $campaign->uploaded_video_url = $Original_video;
                        }
                        if (!empty($campaign->company_id)) {
                            $Company = Company::where('id', $campaign->company_id)->get();
                            $new = [];
                            $new = $Company;
                            $campaign->company_info = $new;
                        }
                        $campaign->id = $this->encrypt_decrypt($campaign->id, 'encrypt');
                        $encrypted_user_id = $this->encrypt_decrypt($user_id, 'encrypt');
                        $campaign_app_download_link = $campaign->app_download_link;
                        $campaign_youtube_url = $campaign->youtube_video_url;
                        if (!empty($campaign_app_download_link)) {
                            //check if URL contains ?
                            if (strpos($campaign_app_download_link, '?') !== false) {
                                $campaign_app_download_link = $campaign_app_download_link . '&';
                            } else {
                                $campaign_app_download_link = $campaign_app_download_link . '?';
                            }
                            $campaign_app_download_link = $campaign_app_download_link . 'ref=' . $encrypted_user_id . '&utm_source=pon&utm_medium=d4c&utm_campaign=' . $campaign->id;
                            $campaign->app_download_link = $campaign_app_download_link;
                        }
                        if (!empty($campaign_youtube_url)) {
                            $video_id_array = parse_str(parse_url($campaign_youtube_url, PHP_URL_QUERY), $my_array_of_vars);
                            $video_id = $my_array_of_vars['v'];
                            $campaign->video_id = $video_id;
                        } else {
                            $campaign->video_id = '';
                        }
                        $news[] = $campaign;
                    }
                    $data = $news;
                }
                if (!empty($data)) {
                    return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language), 200);
                } else {
                    return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), null, 201);
                }
            } else {
                return $this->sendError(Lang::get("campaign.task_not_found", array(), $this->selected_language), null, 201);
            }
        } else {
            return $this->sendError(Lang::get("campaign.user_not_found", array(), $this->selected_language), null, 401);
        }
    }
    /**
     * @Target_Subtype - This API is used for get list of target subtypes by target type ID.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function Target_Subtype(Request $request)
    {

        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $target_types = TargetType::where('id', $requestData['target_id'])->get();
            $target_count  = count($target_types);
            if (!empty($target_count)) {
                $data = [];
                foreach ($target_types as $target_type) {
                    if (!empty($target_type->id)) {
                        $target_subtype = TargetSubtype::where('target_id', $target_type->id)->get();
                        $news = [];
                        $news = $target_subtype;
                    }
                }
                $data['target_types'] = $target_types;
                $data['target_subtype'] = $news;
                return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language), 200);
            } else {
                return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), json_decode("{}"), 201);
            }
        }
    }
    /**
     * @GetCurrentCampaigns - This API is used for get all current running campaigns for brand(logged in).
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetCurrentCampaigns(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetCurrentCampaigns";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $user_id = Auth::id();
        $ids = User::where('user_type', 1)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $user = User::find($user_id);
            if (!empty($user)) {
                $company = Company::where('user_id', $user->id)->first();
                if (!empty($company)) {
                    $admin_is_approved = "APPROVED";
                    $campaigns = Campaign::select(
                        'id',
                        'company_id',
                        'campaign_type',
                        'campaign_type_name',
                        'campaign_name',
                        'start_date',
                        'end_date',
                        'sub_total',
                        'campaign_image',
                        'created_at',
                        'active',
                        'is_start',
                        'campaign_status',
                        'is_approved',
                        'user_target',
                        'is_budget_revised',
                        'note'
                    )->where('start_date', '<=', date('Y-m-d'))
                        ->where('end_date', '>=', date('Y-m-d'))
                        ->where('company_id', $company->id)
                        ->whereIn('campaign_status', ['APPROVED', 'DRAFT'])
                        ->where('active', 1)
                        ->orderBy('id', 'DESC')
                        ->get();
                    $campaign_count = count($campaigns);
                    if ($campaign_count) {
                        $data = [];
                        foreach ($campaigns as $campaign) {
                            if (!empty($campaign->campaign_image)) {
                                $Original = URL::to('/') . '/' . 'uploads/user_files/' . $campaign->campaign_image;
                                $campaign->campaign_image = $Original;
                            }
                            if (!empty($campaign->id)) {
                                $campaign_debit_count = BrandWallet::where('campaign_id', $campaign->id)->sum('debit');
                                $new_array = [];
                                // calculation starts
                                $new_array['spend_euro'] = $campaign_debit_count;
                                $new_array['left_euro'] = $campaign->sub_total - $campaign_debit_count;
                                $new_array['total_budget'] =  $campaign->sub_total;
                                $campaign->campaign_details = $new_array;
                            }
                            if (!empty($campaign->company_id)) {
                                $Company = Company::select('id', 'company_name')->where('id', $campaign->company_id)->get();
                                $new = [];
                                $new = $Company;
                                $campaign->company_info = $new;
                            }
                            $payment_data = PaymentHistory::select('transaction_type')->where('campaign_id', $campaign->id)->latest('id')->first();
                            $campaign->transaction_type = ($payment_data) ? $payment_data->transaction_type : "";
                        }
                        $data = $campaigns;
                        return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language), 200);
                    } else {
                        return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), null, 201);
                    }
                } else {
                    return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 201);
                }
            }
        } else {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 401);
        }
    }
    /**
     * @GetProgrammedCampaigns - This API is used for get all programmed(Future Campaign) campaigns for brand(logged in).
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetProgrammedCampaigns(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetProgrammedCampaigns";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $user_id = Auth::id();
        $ids = User::where('user_type', 1)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $user = User::find($user_id);
            if (!empty($user)) {
                $company = Company::where('user_id', $user->id)->first();
                if (!empty($company)) {
                    $admin_is_approved = "APPROVED";
                    $campaigns = Campaign::select(
                        'id',
                        'company_id',
                        'campaign_type',
                        'campaign_type_name',
                        'campaign_name',
                        'start_date',
                        'end_date',
                        'sub_total',
                        'campaign_image',
                        'created_at',
                        'active',
                        'is_start',
                        'campaign_status',
                        'is_approved',
                        'user_target',
                        'is_budget_revised',
                        'note'
                    )->where('start_date', '>', date('Y-m-d'))
                        ->where('company_id', $company->id)
                        ->whereIn('campaign_status', ['APPROVED', 'DRAFT'])
                        ->where('active', 1)
                        ->get();
                    $campaign_count = count($campaigns);
                    if ($campaign_count) {
                        $data = [];
                        foreach ($campaigns as $campaign) {
                            if (!empty($campaign->campaign_image)) {
                                $Original = URL::to('/') . '/' . 'uploads/user_files/' . $campaign->campaign_image;
                                $campaign->campaign_image = $Original;
                            }
                            if (!empty($campaign->company_id)) {
                                $Company = Company::select('id', 'company_name')->where('id', $campaign->company_id)->get();
                                $new = [];
                                $new = $Company;
                                $campaign->company_info = $new;
                            }
                            $payment_data = PaymentHistory::select('transaction_type')->where('campaign_id', $campaign->id)->latest('id')->first();
                            $campaign->transaction_type = ($payment_data) ? $payment_data->transaction_type : "";
                        }
                        $data = $campaigns;
                        return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language), 200);
                    } else {
                        return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), null, 201);
                    }
                } else {
                    return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 201);
                }
            } else {
                return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 201);
            }
        } else {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 401);
        }
    }
    /**
     * @GetFinishedCampaigns - This API is used for get all finished(Completed Campaign) campaigns for brand(logged in).
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetFinishedCampaigns(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetFinishedCampaigns";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $user_id = Auth::id();
        $ids = User::where('user_type', 1)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $user = User::find($user_id);
            if (!empty($user)) {
                $company = Company::where('user_id', $user->id)->first();
                $campaigns = Campaign::select(
                    'id',
                    'company_id',
                    'campaign_type',
                    'campaign_type_name',
                    'campaign_name',
                    'start_date',
                    'end_date',
                    'sub_total',
                    'campaign_image',
                    'created_at',
                    'active',
                    'is_start',
                    'campaign_status',
                    'is_approved',
                    'user_target',
                    'is_budget_revised',
                    'note'
                )->where('end_date', '<', date('Y-m-d'))
                    ->where('company_id', $company->id)
                    ->whereIn('campaign_status', ['APPROVED', 'DRAFT'])
                    ->get();
                $campaign_count = count($campaigns);
                if ($campaign_count) {
                    $data = [];
                    foreach ($campaigns as $campaign) {
                        if (!empty($campaign->campaign_image)) {
                            $Original = URL::to('/') . '/' . 'uploads/user_files/' . $campaign->campaign_image;
                            $campaign->campaign_image = $Original;
                        }
                        if (!empty($campaign->id)) {
                            $campaign_debit_count = BrandWallet::where('campaign_id', $campaign->id)->sum('debit');
                            $new_array = [];
                            // calculation starts
                            $new_array['spend_euro'] =  round($campaign_debit_count);
                            $new_array['left_euro'] = $campaign->sub_total - $campaign_debit_count;
                            $new_array['total_budget'] =  $campaign->sub_total;
                            $campaign->campaign_details = $new_array;
                        }
                        if (!empty($campaign->company_id)) {
                            $Company = Company::select('id', 'company_name')->where('id', $campaign->company_id)->get();
                            $new = [];
                            $new = $Company;
                            $campaign->company_info = $new;
                        }
                        $payment_data = PaymentHistory::select('transaction_type')->where('campaign_id', $campaign->id)->latest('id')->first();
                        $campaign->transaction_type = ($payment_data) ? $payment_data->transaction_type : "";
                    }
                    $data = $campaigns;
                    return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language), 200);
                } else {
                    return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), json_decode("{}"), 201);
                }
            }
        } else {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 401);
        }
    }
    /**
     * @GetCampaignById - This API used for get campaign details by ID.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetCampaignById(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetCampaignById";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $user_id = Auth::id();
        $ids = User::where('user_type', 1)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $requestData = $request->json()->all();
            if (count($requestData) > 0) {
                $campaigns = Campaign::where('id', $requestData['campaign_id'])->get();
                if ($campaigns) {
                    $data = [];
                    foreach ($campaigns as $campaign) {
                        if (!empty($campaign->campaign_image)) {
                            $Original = URL::to('/') . '/' . 'uploads/user_files/' . $campaign->campaign_image;
                            $campaign->campaign_image = $Original;
                        }
                        if (!empty($campaign->uploaded_video_url)) {
                            $Original_video = URL::to('/') . '/' . 'uploads/user_files/' . $campaign->uploaded_video_url;
                            $campaign->uploaded_video_url = $Original_video;
                        }
                        if (!empty($campaign->youtube_video_url)) {
                            $video_id_array = parse_str(parse_url($campaign->youtube_video_url, PHP_URL_QUERY), $my_array_of_vars);
                            $video_id = $my_array_of_vars['v'];
                            $campaign->video_id = $video_id;
                        } else {
                            $campaign->video_id = '';
                        }
                        // if (!empty($campaign->company_id)) {
                        //     $Company = Company::where('id', $campaign->company_id)->get();
                        //     $new = [];
                        //     $new = $Company;
                        //     $campaign->company_info = $new;
                        // }
                    }
                    $data = $campaigns;
                    return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language), 200);
                } else {
                    return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), json_decode("{}"), 201);
                }
            } else {
                return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), json_decode("{}"), 201);
            }
        } else {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 401);
        }
    }
    /**
     * @GetProgrammedCampaignsById - This API used for get programmed campaign details by ID.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetProgrammedCampaignsById(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetProgrammedCampaignsById";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $user_id = Auth::id();
        $ids = User::where('user_type', 1)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $requestData = $request->json()->all();
            if (count($requestData) > 0) {
                if (!empty($requestData['campaign_id'])) {
                    $campaigns = Campaign::where('start_date', '>', Carbon::now())
                        ->where('id', $requestData['campaign_id'])
                        ->get();
                    $campaign_count = count($campaigns);
                    if ($campaign_count) {
                        $data = [];
                        foreach ($campaigns as $campaign) {
                            if (!empty($campaign->campaign_image)) {
                                $Original = URL::to('/') . '/' . 'uploads/user_files/' . $campaign->campaign_image;
                                $campaign->campaign_image = $Original;
                            }
                            if (!empty($campaign->uploaded_video_url)) {
                                $Original_video = URL::to('/') . '/' . 'uploads/user_files/' . $campaign->uploaded_video_url;
                                $campaign->uploaded_video_url = $Original_video;
                            }
                            if (!empty($campaign->company_id)) {
                                $Company = Company::where('id', $campaign->company_id)->get();
                                $new = [];
                                $new = $Company;
                                $campaign->company_info = $new;
                            }
                        }
                        $data = $campaigns;
                        return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language), 200);
                    } else {
                        return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), json_decode("{}"), 201);
                    }
                }
            } else {
                return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), json_decode("{}"), 201);
            }
        } else {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 401);
        }
    }
    /**
     * @EditProgrammedCampaigns - This API used for update programmed campaign details.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function EditProgrammedCampaigns(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "EditProgrammedCampaigns";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $user_id = Auth::id();
        $ids = User::where('user_type', 1)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $requestData = $request->json()->all();
            if (count($requestData) > 0) {
                $validator =  Validator::make($requestData, [
                    'campaign_id' => 'required',
                    'campaign_name' => 'required',
                    'campaign_type' => 'required',
                    'start_date' => 'required',
                    'end_date' => 'required',
                    'product_information' => 'required',
                    'cac' => 'required'
                    // 'coins' => 'required',
                    // 'user_target' => 'required'
                ]);
                if ($validator->fails()) {
                    $error = $validator->errors()->first();
                    return $this->sendError($error, null, 400);
                }
                if (!empty($requestData['campaign_id']) and !empty($requestData['campaign_type'])) {
                    $goal_of_campaign = "";
                    $campaign_name = $requestData['campaign_name'];
                    $campaign_type = $requestData['campaign_type'];
                    if (!empty($requestData['goal_of_campaign'])) {
                        $goal_of_campaign = $requestData['goal_of_campaign'];
                    }
                    $start_date = $requestData['start_date'];
                    $end_date = $requestData['end_date'];
                    $product_information = $requestData['product_information'];
                    if (!empty($requestData['country'])) {
                        $country = $requestData['country'];
                    }
                    if (!empty($requestData['start_age'])) {
                        $start_age = $requestData['start_age'];
                    }
                    if (!empty($requestData['end_age'])) {
                        $end_age = $requestData['end_age'];
                    }
                    if (!empty($requestData['gender'])) {
                        $gender = $requestData['gender'];
                    }
                    if (!empty($requestData['campaign_image'])) {
                        $campaign_image = $requestData['campaign_image'];
                    }
                    if (!empty($requestData['uploaded_video_url'])) {
                        $uploaded_video_url = $requestData['uploaded_video_url'];
                    }
                    if (!empty($requestData['youtube_video_url'])) {
                        $youtube_video_url = $requestData['youtube_video_url'];
                    }
                    $cac = $requestData['cac'];
                    // $coins = $requestData['coins'];
                    // $user_target = $requestData['user_target'];
                    $campaign = Campaign::where('id', $requestData['campaign_id'])
                        ->where('campaign_type', $requestData['campaign_type'])
                        ->first();
                    if (!empty($campaign)) {
                        if ($requestData['cac'] >= $campaign->cac) {
                            $campaign->campaign_name = $campaign_name;
                            if (!empty($goal_of_campaign)) {
                                $campaign->goal_of_campaign = $goal_of_campaign;
                            }
                            $campaign->start_date = date("Y-m-d", strtotime($start_date));
                            $campaign->end_date = date("Y-m-d", strtotime($end_date));
                            $campaign->product_information = $product_information;
                            if (!empty($country)) {
                                $campaign->country = $country;
                            } else {
                                $campaign->country = NULL;
                            }
                            if (!empty($start_age)) {
                                $campaign->start_age = $start_age;
                            } else {
                                $campaign->start_age = NULL;
                            }
                            if (!empty($end_age)) {
                                $campaign->end_age = $end_age;
                            } else {
                                $campaign->end_age = NULL;
                            }
                            if (!empty($gender)) {
                                $campaign->gender = $gender;
                            } else {
                                $campaign->gender = NULL;
                            }
                            $campaign->cac = $cac;
                            //calling CalculateCoinUserTarget function
                            if (!empty($campaign->sub_total) and !empty($cac)) {
                                $this->CalculateCoinUserTarget($campaign->sub_total, $cac, $campaign);
                            }
                            //calling image upload function
                            if (!empty($campaign_image)) {
                                $this->image_upload($campaign_image, $campaign);
                            }
                            if ($requestData['campaign_type'] == 'video_plays') {
                                if (empty($uploaded_video_url) && empty($youtube_video_url)) {
                                    return $this->sendError(Lang::get("campaign.campaign_video_required", array(), $this->selected_language), null, 400);
                                }
                                //calling Video upload function
                                if (!empty($uploaded_video_url)) {
                                    $this->video_upload($uploaded_video_url, $campaign);
                                } else {
                                    $campaign->uploaded_video_url = '';
                                }
                                if (!empty($youtube_video_url)) {
                                    $campaign->youtube_video_url = $youtube_video_url;
                                } else {
                                    $campaign->youtube_video_url = '';
                                }
                            } else if ($requestData['campaign_type'] == 'follow') {
                                $selected_social_media_name = $requestData['selected_social_media_name'];
                                $selected_social_media_url = $requestData['selected_social_media_url'];
                                $campaign->selected_social_media_url = $selected_social_media_url;
                                $campaign->selected_social_media_name = $selected_social_media_name;
                            } else if ($requestData['campaign_type'] == 'apps_download') {
                                $app_download_link = $requestData['app_download_link'];
                                $campaign->app_download_link = $app_download_link;
                            } else if ($requestData['campaign_type'] == 'click_websites') {
                                $website_url = $requestData['website_url'];
                                $campaign->website_url = $website_url;
                            }
                            if ($campaign->is_approved == "REJECTED") {
                                $campaign->is_approved = "PENDING";
                            }
                            $campaign->save();
                            if (!empty($campaign)) {
                                $Campaign_Details = Campaign::find($campaign->id);
                                if (!empty($Campaign_Details->campaign_image)) {
                                    $Original = URL::to('/') . '/' . 'uploads/user_files/' . $Campaign_Details->campaign_image;
                                    $Campaign_Details['campaign_image'] = $Original;
                                }
                                if (!empty($campaign->uploaded_video_url) and $requestData['campaign_type'] == 'video_plays') {
                                    $Original_video = URL::to('/') . '/' . 'uploads/user_files/' . $campaign->uploaded_video_url;
                                    $Campaign_Details['uploaded_video_url'] = $Original_video;
                                }
                                return $this->sendResponse($Campaign_Details, Lang::get("campaign.campaign_updated_success", array(), $this->selected_language), 200);
                            }
                        } else {
                            return $this->sendError(Lang::get("campaign.campaign_cac_error", array(), $this->selected_language), null, 201);
                        }
                    } else {
                        return $this->sendError(Lang::get("campaign.campaign_not_found", array(), $this->selected_language), null, 201);
                    }
                } else {
                    return $this->sendError(Lang::get("campaign.campaign_type_id_required", array(), $this->selected_language), null, 400);
                }
            } else {
                return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), json_decode("{}"), 400);
            }
        } else {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 401);
        }
    }
    /**
     * @UpdateCampaignBudget - This API used for Increase Campaign Budget and Pay Pending(Draft) Payment.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function UpdateCampaignBudget(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "UpdateCampaignBudget";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $user_id = Auth::id();
        $ids = User::where('user_type', 1)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $requestData = $request->json()->all();
            if (count($requestData) > 0) {
                $validator =  Validator::make($requestData, [
                    "campaign_id" => 'required',
                    "user_target" => 'required',
                    "cac" => 'required',
                    "coins" => 'required',
                    "sub_total" => 'required',
                    "tax_percentage" => 'required',
                    "campaign_name" => 'required',
                    "campaign_type" => 'required',
                    "start_date" => 'required',
                    "end_date" => 'required',
                    "product_information" => 'required',
                    // "transaction_id" => 'required',
                    // "transaction_date" => 'required',
                    "transaction_type" => 'required',
                    "transaction_status" =>  'required',
                    // "paypal_id" => 'required'
                ]);
                if ($validator->fails()) {
                    $error = $validator->errors()->first();
                    return $this->sendError($error, null, 400);
                }
                $campaign_name = $requestData['campaign_name'];
                $campaign_type = $requestData['campaign_type'];
                if (!empty($requestData['goal_of_campaign'])) {
                    $goal_of_campaign = $requestData['goal_of_campaign'];
                }
                $start_date = $requestData['start_date'];
                $end_date = $requestData['end_date'];
                $product_information = $requestData['product_information'];
                if (!empty($requestData['country'])) {
                    $country = $requestData['country'];
                }
                if (!empty($requestData['start_age'])) {
                    $start_age = $requestData['start_age'];
                }
                if (!empty($requestData['end_age'])) {
                    $end_age = $requestData['end_age'];
                }
                if (!empty($requestData['gender'])) {
                    $gender = $requestData['gender'];
                }
                if (!empty($requestData['campaign_image'])) {
                    $campaign_image = $requestData['campaign_image'];
                }
                if (!empty($requestData['uploaded_video_url'])) {
                    $uploaded_video_url = $requestData['uploaded_video_url'];
                }
                $youtube_video_url = '';
                if (!empty($requestData['youtube_video_url'])) {
                    $youtube_video_url = $requestData['youtube_video_url'];
                }
                // lets check if campaign found for given details                
                $campaign_found = Campaign::where('id', $requestData['campaign_id'])
                    ->where('campaign_type', $campaign_type)
                    ->first();
                //if data not found lets do update and payment for that
                if (!empty($campaign_found)) {
                    $email = $ids->email;
                    if (!empty($campaign_found->id) and $campaign_found->campaign_status !== "DRAFT") {
                        $get_campaign_count = UserCoins::where('campaign_id', '=', $requestData['campaign_id'])
                            ->where('credit', '!=', '0')
                            ->count();
                    }
                    $campaigns = Campaign::where('id', $requestData['campaign_id'])
                        ->first();
                    if ($campaign_found->campaign_status == "DRAFT" or $requestData['user_target'] > $get_campaign_count) {
                        $old_sub_total = $campaigns->sub_total;
                        $PaymentHistory = new PaymentHistory;
                        $PaymentHistory->user_id = $user_id;
                        $PaymentHistory->campaign_id = $requestData['campaign_id'];
                        $PaymentHistory->transaction_id = ($requestData['transaction_id']) ? $requestData['transaction_id'] : null;
                        $PaymentHistory->transaction_date = ($requestData['transaction_date']) ? $requestData['transaction_date'] : date('Y-m-d');
                        $PaymentHistory->transaction_type = $requestData['transaction_type'];
                        $PaymentHistory->transaction_status = $requestData['transaction_status'];
                        $PaymentHistory->paypal_id = ($requestData['paypal_id']) ? $requestData['paypal_id'] : null;
                        $campaign_discount =  env("INVOICE_DISCOUNT", null);
                        $sub_total = $requestData['sub_total'];
                        $final_total = $sub_total - $campaign_discount;
                        $tax_percentage = $requestData['tax_percentage'];
                        $tax_value = ($final_total * $tax_percentage) / 100;
                        $payment = $final_total + $tax_value;
                        $PaymentHistory->grand_total = $payment;
                        $PaymentHistory->paypal_response = json_encode($requestData, true);
                        $PaymentHistory->save();
                        $inserted_payment_id = $PaymentHistory->id;
                        if ($PaymentHistory->transaction_status == "COMPLETED") {
                            $campaigns->campaign_name = $campaign_name;
                            $campaigns->cac = $requestData['cac'];
                            $campaigns->tax_value = $requestData['tax_percentage'];
                            $cac = $requestData['cac'];
                            //calling CalculateCoinUserTarget function
                            if (!empty($sub_total) and !empty($cac)) {
                                $this->CalculateCoinUserTarget($sub_total, $cac, $campaigns);
                            }
                            // $campaigns->coins = $requestData['coins'];
                            // $campaigns->user_target = $requestData['user_target'];
                            if ($campaign_found->campaign_status == "DRAFT") {
                                $campaigns->sub_total = $sub_total;
                                $tax_values = ($sub_total * $requestData['tax_percentage']) / 100;
                                $payments = $sub_total + $tax_values;
                                $campaigns->total_budget = $payments;
                                $campaigns->campaign_status = 'APPROVED';
                            } else {
                                $updated_sub_total = $old_sub_total + $sub_total;
                                $campaigns->sub_total = $updated_sub_total;
                                $tax_values = ($updated_sub_total * $requestData['tax_percentage']) / 100;
                                $payments = $updated_sub_total + $tax_values;
                                $campaigns->total_budget = $payments;
                                $campaigns->is_budget_revised = 'YES';
                            }
                            if (!empty($goal_of_campaign)) {
                                $campaigns->goal_of_campaign = $goal_of_campaign;
                            }
                            $campaigns->start_date = date("Y-m-d", strtotime($start_date));
                            $campaigns->end_date = date("Y-m-d", strtotime($end_date));
                            $campaigns->product_information = $product_information;
                            if (!empty($country)) {
                                $campaigns->country = $country;
                            } else {
                                $campaigns->country = NULL;
                            }
                            if (!empty($start_age)) {
                                $campaigns->start_age = $start_age;
                            } else {
                                $campaigns->start_age = NULL;
                            }
                            if (!empty($end_age)) {
                                $campaigns->end_age = $end_age;
                            } else {
                                $campaigns->end_age = NULL;
                            }
                            if (!empty($gender)) {
                                $campaigns->gender = $gender;
                            } else {
                                $campaigns->gender = NULL;
                            }
                            //calling image upload function
                            if (!empty($campaign_image)) {
                                $this->image_upload($campaign_image, $campaigns);
                            }
                            if ($requestData['campaign_type'] == 'video_plays') {
                                if (empty($uploaded_video_url) && empty($youtube_video_url)) {
                                    return $this->sendError(Lang::get("campaign.campaign_video_required", array(), $this->selected_language), null, 400);
                                }
                                //calling Video upload function
                                if (!empty($uploaded_video_url)) {
                                    $this->video_upload($uploaded_video_url, $campaigns);
                                } else {
                                    $campaigns->uploaded_video_url = '';
                                }
                                if (!empty($youtube_video_url)) {
                                    $campaigns->youtube_video_url = $youtube_video_url;
                                } else {
                                    $campaigns->youtube_video_url = '';
                                }
                            } else if ($requestData['campaign_type'] == 'follow') {
                                $selected_social_media_name = $requestData['selected_social_media_name'];
                                $selected_social_media_url = $requestData['selected_social_media_url'];
                                $campaigns->selected_social_media_url = $selected_social_media_url;
                                $campaigns->selected_social_media_name = $selected_social_media_name;
                            } else if ($requestData['campaign_type'] == 'apps_download') {
                                $app_download_link = $requestData['app_download_link'];
                                $campaigns->app_download_link = $app_download_link;
                            } else if ($requestData['campaign_type'] == 'click_websites') {
                                $website_url = $requestData['website_url'];
                                $campaigns->website_url = $website_url;
                            }
                            if ($campaigns->is_approved == "REJECTED") {
                                $campaigns->is_approved = "PENDING";
                            }
                            if ($campaign_found->campaign_status !== "DRAFT") {
                                $closing_amount = BrandWallet::where('user_id', $user_id)
                                    ->where('campaign_id', $requestData['campaign_id'])
                                    ->latest('id')->first();
                            }
                            $BrandWallet = new BrandWallet;
                            $BrandWallet->user_id = $user_id;
                            $BrandWallet->campaign_id = $campaigns->id;
                            if ($campaign_found->campaign_status == "DRAFT") {
                                $BrandWallet->opening_balance = 0.00;
                            } else {
                                $BrandWallet->opening_balance = $closing_amount->closing_balance;
                            }
                            $BrandWallet->transaction_date = $requestData['transaction_date'];
                            $BrandWallet->credit = $sub_total;
                            if ($campaign_found->campaign_status == "DRAFT") {
                                $BrandWallet->closing_balance = 0.00 + $sub_total;
                            } else {
                                $BrandWallet->closing_balance = $closing_amount->closing_balance + $sub_total;
                            }
                            $BrandWallet->cac = $requestData['cac'];
                            $BrandWallet->save();
                            if ($BrandWallet) {
                                $campaigns->save();
                                if (!empty($campaigns)) {
                                    //lets update brand wallet balance
                                    BrandWalletBalance::updateBrandBalance($user_id);

                                    //lets create invoice for this campaign
                                    $Invoice_indb = Invoice::latest('id')->first();
                                    $Invoice = new Invoice;
                                    if (!empty($Invoice_indb)) {
                                        $final_invoice = $Invoice_indb->invoice_id + 1;
                                        $Invoice->invoice_id = str_pad($final_invoice, 5, "0", STR_PAD_LEFT);
                                    } else {
                                        $Invoice->invoice_id = env("INVOICE_START_FROM", null);
                                    }
                                    $Invoice->user_id = $user_id;
                                    $invoice_discount =  env("INVOICE_DISCOUNT", null);
                                    $final_total = $sub_total - $invoice_discount;
                                    $tax_percentage = $requestData['tax_percentage'];
                                    $Invoice->campaign_id = $campaigns->id;
                                    $Invoice->cac = $requestData['cac'];
                                    $Invoice->sub_total = $sub_total;
                                    $Invoice->discount = $invoice_discount;
                                    $Invoice->final_total = $final_total;
                                    $Invoice->tax_percentage = $tax_percentage;
                                    $tax_value = ($final_total * $tax_percentage) / 100;
                                    $Invoice->tax_value = $tax_value;
                                    $Invoice->grand_total = $final_total + $tax_value;
                                    $Invoice->payment_id =   $Invoice->payment_id = ($requestData['transaction_id']) ? $requestData['transaction_id'] : $requestData['transaction_type'];
                                    $Invoice->payment_history_id = $inserted_payment_id;
                                    $Invoice->save();
                                    //sending email for campaign budget update to brand with attachment and admin for brand updated budget
                                    if (!empty($Invoice)) {

                                        $show =  Invoice::select(
                                            'invoices.*',
                                            'campaigns.campaign_name',
                                            'campaigns.campaign_type',
                                            'campaigns.user_target',
                                            'companies.company_name',
                                            'companies.phone',
                                            'users.city'
                                        )
                                            ->join('campaigns', 'invoices.campaign_id', '=', 'campaigns.id')
                                            ->join('companies', 'invoices.user_id', '=', 'companies.user_id')
                                            ->join('users', 'invoices.user_id', '=', 'users.id')
                                            ->where('invoices.user_id', $user_id)
                                            ->where('invoices.id', $Invoice->id)
                                            ->first();
                                        if (empty($show)) {
                                            return $this->sendError(Lang::get("campaign.invoice_not_found", array(), $this->selected_language), null, 201);
                                        }
                                        $pdf = PDF::loadView($this->selected_language . '.pdf', compact('show'));
                                        $grand_total = $payment;
                                        $data = [
                                            'company_name' => $show->company_name,
                                            'campaign_name' => $show->campaign_name,
                                            'grand_total' => $grand_total
                                        ];
                                        $to = $email;
                                        $emails =  env("MAIL_FROM_ADDRESS", null);
                                        $from_name =  env("MAIL_FROM_NAME", null);
                                        $from = $emails;
                                        if ($campaign_found->campaign_status == "DRAFT") {
                                            // $subject = ($templates_lang === "es") ? "Campaña creada con éxito- " : "Campaign Created Successfully- ";
                                            $subject = Lang::get("campaign.campaign_created_success_subject", array(), $this->selected_language);
                                        } else {
                                            // $subject = ($templates_lang === "es") ? "Aumento del presupuesto de la campaña- " : "Campaign budget increased- ";
                                            $subject = Lang::get("campaign.campaign_budget_increased_subject", array(), $this->selected_language);
                                        }

                                        $subject .= $show->campaign_name;
                                        try {
                                            if ($campaign_found->campaign_status == "DRAFT") {
                                                Mail::send($this->selected_language . '.auth.emails.paymentconfirmation', $data, function ($msg)
                                                use ($to, $from, $from_name, $subject, $pdf) {
                                                    $msg->to($to)->from($from, $from_name)->subject($subject);
                                                    $msg->attachData($pdf->output(), Lang::get("campaign.campaign_invoice_filename", array(), $this->selected_language) . '.pdf');
                                                });
                                                $admin_data = [
                                                    'brand_name' => $show->company_name,
                                                    'campaign_name' => $show->campaign_name,
                                                    'campaign_type' => $campaigns->campaign_type,
                                                    'start_date' => $campaigns->start_date,
                                                    'end_date' => $campaigns->end_date,
                                                    'user_target' => $campaigns->user_target,
                                                    'cac' => $campaigns->cac,
                                                    'sub_total' => $campaigns->sub_total,
                                                    'grand_total' => $grand_total
                                                ];
                                                $to = env("ADMIN_EMAIL", null);
                                                $emails =  env("MAIL_FROM_ADDRESS", null);
                                                $from_name =  env("MAIL_FROM_NAME", null);
                                                $from = $emails;
                                                // $subject = ($templates_lang === "es") ? "Se requiere aprobación de campaña- " : "Campaign approval required- ";
                                                $subject = Lang::get("campaign.campaign_approval_required_subject", array(), $this->selected_language);
                                                $subject .= $campaign_name;
                                                Mail::send($this->selected_language . '.auth.emails.admin_campaign_created', $admin_data, function ($msg)
                                                use ($to, $from, $from_name, $subject) {
                                                    $msg->to($to)->from($from, $from_name)->subject($subject);
                                                });
                                            } else {
                                                Mail::send($this->selected_language . '.auth.emails.updatecampaignbudget', $data, function ($msg)
                                                use ($to, $from, $from_name, $subject, $pdf) {
                                                    $msg->to($to)->from($from, $from_name)->subject($subject);
                                                    $msg->attachData($pdf->output(), Lang::get("campaign.campaign_invoice_filename", array(), $this->selected_language) . '.pdf');
                                                });
                                            }
                                        } catch (ClientException $e) {
                                            // Get error here
                                            $emailResult = "Email not sent";
                                            $failed_response = json_decode($emailResult, true);
                                        }
                                    } else {
                                        return $this->sendError(Lang::get("campaign.invoice_creation_failed", array(), $this->selected_language), null, 201);
                                    }
                                }
                                return $this->sendResponse($BrandWallet, Lang::get("campaign.payment_success", array(), $this->selected_language), 200);
                            } else {
                                return $this->sendError(Lang::get("common.something_went_wrong", array(), $this->selected_language), null, 500);
                            }
                        } else {
                            return $this->sendError(Lang::get("campaign.payment_failed", array(), $this->selected_language), null, 500);
                        }
                    } else {
                        return $this->sendError(Lang::get("campaign.campaign_targt_user_limit_error", array(), $this->selected_language), null, 201);
                    }
                } else {
                    return $this->sendError(Lang::get("campaign.campaign_not_found", array(), $this->selected_language), null, 201);
                }
            } else {
                return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), json_decode("{}"), 400);
            }
        } else {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 401);
        }
    }
    /**
     * @StopRunningCampaign - This API is used for Stop Running Campaign in-between campaign disappear from user side.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function StopRunningCampaign(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "StopRunningCampaign";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $user_id = Auth::id();
        $ids = User::where('user_type', 1)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $requestData = $request->json()->all();
            if (count($requestData) > 0) {
                $validator =  Validator::make($requestData, [
                    'campaign_id' => 'required',
                    'is_start' => 'required',
                ]);
                if ($validator->fails()) {
                    $error = $validator->errors()->first();
                    return $this->sendError($error, null, 400);
                }
                $campaign_id = $requestData['campaign_id'];
                if (!empty($campaign_id)) {
                    $campaign = Campaign::where('id', $campaign_id)
                        ->first();
                    if (empty($campaign)) {
                        return $this->sendError(Lang::get("campaign.campaign_not_found", array(), $this->selected_language), null, 201);
                    }
                    $is_start = $requestData['is_start'];
                    $campaign->is_start = $is_start;
                    $campaign->save();
                    if (!empty($campaign)) {
                        $data = [];
                        $data['is_start'] = $campaign->is_start;
                        return $this->sendResponse($data, Lang::get("campaign.campaign_status_changed", array(), $this->selected_language));
                    } else {
                        return $this->sendError(Lang::get("common.failed", array(), $this->selected_language), json_decode("{}"), 201);
                    }
                }
            } else {
                return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), null, 400);
            }
        } else {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), null, 401);
        }
    }
    /**
     * @CreditOnCampaignComplete - This API is used for credit coins when user completes the campaign.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function CreditOnCampaignComplete(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "CreditOnCampaignComplete";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $user_id = Auth::id();
        $ids = User::where('user_type', 2)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $requestData = $request->json()->all();
            if (count($requestData) > 0) {
                $validator =  Validator::make($requestData, [
                    'campaign_id' => 'required',
                    'campaign_type' => 'required'
                ]);
                if ($validator->fails()) {
                    $error = $validator->errors()->first();
                    return $this->sendError($error, null, 400);
                }
                $camp_ids = $requestData['campaign_id'];
                $campaignid = $this->encrypt_decrypt($camp_ids, 'decrypt');
                $closing_data =  BrandWallet::select(
                    'brand_wallets.closing_balance',
                    'campaigns.coins'
                )
                    ->join('campaigns', 'brand_wallets.campaign_id', '=', 'campaigns.id')
                    ->where('campaign_id', $campaignid)
                    ->latest('brand_wallets.id')->first();
                if (!empty($closing_data)) {
                    //is_bool($closing_data->closing_balance >= $closing_data->coins * 0.006*2);
                    $euro = round($closing_data->coins * 0.006 * 2, 2);
                    if ($euro >= $closing_data->closing_balance) {
                        return $this->sendError(Lang::get("campaign.campaign_expired", array(), $this->selected_language), null, 201);
                        exit;
                    }
                    $get_campaign_count = UserCoins::where('user_id', $user_id)
                        ->where('campaign_id', '=', $campaignid)
                        ->where('credit', '!=', '0')
                        ->first();
                    if (!empty($get_campaign_count)) {
                        return $this->sendError(Lang::get("campaign.campaign_task_already_completed", array(), $this->selected_language), null, 201);
                    }
                    $user = User::where('id', $user_id)
                        ->where('user_type', 2)->first();
                    if (!empty($user)) {
                        $user_id = $user->id;
                        $campaigns = Campaign::where('id', $campaignid)
                            ->where('campaign_type', $requestData['campaign_type'])
                            ->first();
                        if (!empty($campaigns)) {
                            //Check if it is a download campaign
                            if (!empty($campaigns->app_download_link)) {
                                //Check if user has download the app (brand have sent the request)
                                $user_action = Action::where('campaign_id', $campaigns->id)->where('user_id', $user_id)->where('action_type_id', 1)->first();
                                //If user has not download the app, throw an error
                                if (empty($user_action)) {
                                    return $this->sendError(Lang::get("campaign.app_not_downloaded", array(), $this->selected_language), null, 201);
                                }
                            }
                            $closing_data = BrandWallet::where('campaign_id', $campaigns->id)->latest('id')->first();
                            $new_debit_value = round(($campaigns->coins * 0.006) * 2, 2);
                            $BrandWallet = new BrandWallet;
                            $BrandWallet->user_id = $closing_data->user_id;
                            $BrandWallet->campaign_id = $campaigns->id;
                            $BrandWallet->opening_balance = $closing_data->closing_balance;
                            $BrandWallet->debit = $new_debit_value;
                            $BrandWallet->closing_balance = $closing_data->closing_balance - $new_debit_value;
                            $BrandWallet->save();
                            if (!empty($BrandWallet)) {
                                //updating brand wallet
                                BrandWalletBalance::updateBrandBalance($user_id);

                                //crediting coins
                                $user_coins_credit = UserCoins::creditCoins(array(
                                    'user_id' => $user_id,
                                    'campaign_id' => $campaigns->id, // new user reward id
                                    'credit' => $campaigns->coins
                                ));

                                if ($user_coins_credit) {
                                    $compay_details = Company::where('id',  $campaigns->company_id)->first();
                                    $company_user_id = $compay_details->user_id;
                                    $CampaignClick = new CampaignClick;
                                    $CampaignClick->brand_id = $company_user_id;
                                    $CampaignClick->campaign_id = $campaigns->id;
                                    $CampaignClick->user_id = $user_id;
                                    $CampaignClick->is_completed = '1';
                                    $CampaignClick->save();
                                    $data['coins'] = $campaigns->coins;

                                    return $this->sendResponse($data, Lang::get("campaign.campaign_coins_credited", array(), $this->selected_language), 200);
                                }
                            } else {
                                return $this->sendError(Lang::get("campaign.campaign_coins_crediting_failed", array(), $this->selected_language), null, 201);
                            }
                        } else {
                            return $this->sendError(Lang::get("campaign.campaign_not_found", array(), $this->selected_language), null, 201);
                        }
                    } else {
                        return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), null, 201);
                    }
                } else {
                    return $this->sendError(Lang::get("campaign.campaign_not_found", array(), $this->selected_language), null, 201);
                }
            }
        } else {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 401);
        }
    }
    /**
     * @GetCampaignPosition - This API is used for get campaign position at user side.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetCampaignPosition(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetCampaignPosition";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $user_id = Auth::id();
        $ids = User::where('user_type', 1)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $requestData = $request->json()->all();
            if (count($requestData) > 0) {
                $validator =  Validator::make($requestData, [
                    'coin' => 'required',
                ]);
                if ($validator->fails()) {
                    $error = $validator->errors()->first();
                    return $this->sendError($error, null, 400);
                }
                $coin_value = $requestData['coin'];
                $is_start = 1;
                $active = 1;
                $min_coin = 100;
                DB::statement(DB::raw('SET @row_number = 0'));
                $position = DB::select(DB::raw('SELECT t.position FROM
                    (SELECT (@row_number:=@row_number + 1) AS position,
                    id,
                    coins,
                    is_start,
                    active,
                    deleted_at,start_date,end_date
                    FROM campaigns
                    Where start_date <= CURDATE()
                    AND end_date >= CURDATE()
                    AND is_start = "' . $is_start . '"
                    AND active = "' . $active . '"
                    AND deleted_at IS NULL ORDER BY coins DESC) as t where t.coins < "' . $coin_value . '" limit 1'));
                if (!empty($position)) {
                    return $this->sendResponse($position, Lang::get("common.success", array(), $this->selected_language), 200);
                } else {
                    DB::statement(DB::raw('SET @row_number = 0'));
                    $position_new = DB::select(DB::raw('SELECT (@row_number:=@row_number + 1) AS position,id,coins,is_start,
                        active,
                        deleted_at,start_date,end_date
                        FROM campaigns 
                        Where start_date <= CURDATE()
                        AND end_date >= CURDATE()
                        AND is_start = "' . $is_start . '"
                        AND active = "' . $active . '"
                        AND deleted_at IS NULL
                        ORDER BY position DESC'));
                    if (!empty($position_new)) {
                        $last_position = array();
                        if (!empty($position_new[0]->position)) {
                            $last_position['position'] =  $position_new[0]->position + 1;
                        }
                        $data[]['position'] = $last_position['position'];
                    } else {
                        $data[]['position'] = '1';
                    }
                    return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language), 200);
                }
            } else {
                return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), null, 400);
            }
        } else {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), null, 401);
        }
    }

    /**
     * @RedeemRewards - This API is used for redeem rewards at user side
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function RedeemRewards(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "RedeemRewards";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        //check authorised user  
        $user_id = Auth::id();
        $ids = User::where('user_type', 2)->where('id', $user_id)->first();
        if (empty($ids)) {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), null, 401);
        }

        //check request body
        $requestData = $request->json()->all();
        if (empty($requestData) || count($requestData) <= 0) {
            return $this->sendError(Lang::get("common.request_empty", array(), $this->selected_language), null, 201);
        }

        //check validation 
        $validator =  Validator::make($requestData, [
            'rewards_id' => 'required',
            'receiver' => 'required|email'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return $this->sendError($error, null, 400);
        }

        //check reward 
        $rewards_data = Rewards::where('id', $requestData['rewards_id'])->first();
        if (empty($rewards_data)) {
            return $this->sendError(Lang::get("campaign.reward_not_found", array(), $this->selected_language), null, 201);
        }
        // confirm reward is paypal
        if ($rewards_data->id != '2') {
            return $this->sendError(Lang::get("campaign.campaign_wrong_reward", array(), $this->selected_language), null, 201);
        }

        $closing_amount = UserCoins::where('user_id', $user_id)->latest('id')->first();
        if (empty($closing_amount)) {
            return $this->sendError(Lang::get("campaign.wallet_balance_not_found", array(), $this->selected_language), null, 201);
        }
        $wallet_balance['wallet_balance'] = $closing_amount->closing_balance;

        //check amount
        if ($rewards_data->amount <= 0) {
            return $this->sendError(Lang::get("campaign.reward_amount_is_not_defined", array(), $this->selected_language), null, 201);
        }

        //payable amount
        $payout_value = $rewards_data->amount;
        if (floor($payout_value) == $payout_value) {
            $payout_value = floor($payout_value);
        }

        //check user has enough coins!
        if ($closing_amount->closing_balance < $rewards_data->minimum_coins) {
            return $this->sendError(Lang::get("campaign.not_enough_coins", array(), $this->selected_language), null, 201);
        }
        $paypal_auth_uri = env("PAYPAL_AUTH_URL", null);
        $paypal_clientId = env("CLIENT_ID", null);
        $paypal_secret = env("SECRET", null);
        $payouts_url = env("PAYOUTS_URL", null);
        if (empty($paypal_auth_uri) || empty($paypal_clientId) || empty($paypal_secret) || empty($payouts_url)) {
            return $this->sendError(Lang::get("common.missing_require_data", array(), $this->selected_language), null, 201);
        }

        //$rewards_data->id = 2 means id 2 is for paypal reward
        if ($rewards_data->id = '2') {
            try {
                $paypal_auth_data = array(
                    'headers' =>
                    [
                        'Accept' => 'application/json',
                        'Accept-Language' => 'en_US',
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'body' => 'grant_type=client_credentials',
                    'auth' => [$paypal_clientId, $paypal_secret, 'basic']
                );

                $paypal_auth_response_data = GeneralHelper::RequestCurl('POST', $paypal_auth_uri, $paypal_auth_data);
                if (!$paypal_auth_response_data['status']) {
                    return $this->sendError(Lang::get("campaign.paypal_connection_failed", array(), $this->selected_language), null, 201);
                }

                //check for token
                $paypal_auth_token = isset($paypal_auth_response_data['data']['access_token']) ? $paypal_auth_response_data['data']['access_token'] : "";
                if (empty($paypal_auth_token)) {
                    return $this->sendError(Lang::get("campaign.paypal_authorization_failed", array(), $this->selected_language), null, 201);
                }

                //go for payout process
                if (!empty($paypal_auth_token)) {
                    $paypal_log_array = array(
                        'user_id' => $user_id,
                        'rewards_id' => $rewards_data->id,
                        'transaction_status' => 'FAILED',
                        'transaction_type' => 'paypal',
                        'payment_mode' => 'payouts',
                        'grand_total' => $payout_value
                    );

                    $randomString = Str::random(30);
                    $sender_item_id = "U" . $user_id . "-" . date('YmdHis');
                    $paypalPayoutBody = '{
                                "sender_batch_header": {
                                "sender_batch_id": "' . $randomString . '",
                                "email_subject": "You have a payout!",
                                "email_message": "You have received a payout! Thanks for using our service!"
                                },
                                "items": [
                                {
                                    "recipient_type": "EMAIL",
                                    "amount": {
                                        "value": "' . $payout_value . '",
                                        "currency": "' . $rewards_data->currency_code . '"
                                    },
                                    "note": "Thanks for your patronage!",
                                    "sender_item_id": "' . $sender_item_id . '",
                                    "receiver": "' . $requestData['receiver'] . '"
                                }]
                            }';
                    $paypalPayoutData = array(
                        'headers' =>
                        [
                            'Content-Type' => 'application/json',
                            'Authorization' => "Bearer $paypal_auth_token",
                        ],
                        'body' => $paypalPayoutBody
                    );

                    //let's do payout request
                    $client = new \GuzzleHttp\Client();
                    $paymentResponse = $client->request('POST', $payouts_url, $paypalPayoutData);
                    $paymentBody = json_decode($paymentResponse->getBody(), true);
                    $statusCode = $paymentResponse->getStatusCode();

                    //check response & status code
                    if (empty($paymentBody) || !isset($paymentBody['batch_header']['payout_batch_id']) || !isset($paymentBody['batch_header']['batch_status'])) {
                        return $this->sendError(Lang::get("campaign.paypal_reward_request_failed", array(), $this->selected_language), null, 201);
                    }

                    //check payout batch status
                    if (in_array($paymentBody['batch_header']['batch_status'], array("DENIED", "CANCELED"))) {
                        return $this->sendError(Lang::get("campaign.paypal_reward_request_denied", array(), $this->selected_language), null, 201);
                    }

                    //save the payment log of paypal's first response of creating payout request
                    $paypal_log_array['paypal_reference_number'] = $paymentBody['batch_header']['payout_batch_id'];  // store payout batch id
                    $paypal_log_array['paypal_request'] = json_encode(array("url" => $payouts_url, "request" => $paypalPayoutData), true);
                    $paypal_log_array['paypal_response'] = json_encode($paymentBody, true);
                    $paypal_log_array['transaction_status'] = $paymentBody['batch_header']['batch_status'];
                    $payment_history_id = PaymentHistory::savePaymentLog($paypal_log_array);

                    //whether the status is success or process let's debit the coins from user account
                    $user_reward_id = UserRewards::addUserReward(array(
                        "user_id" => $user_id,
                        "reward_id" => $rewards_data->id,
                        "redeem_coins" => $rewards_data->minimum_coins,
                        "description" => $rewards_data->description,
                        "payment_history_id" => $payment_history_id
                    ));

                    //check payout batch status 
                    if ($paymentBody['batch_header']['batch_status'] === "SUCCESS") {
                        $payout_batch_id = $paymentBody['batch_header']['payout_batch_id'];

                        //delay for confirm the status of payout-item
                        usleep(5000);

                        //let's check confirm payment call
                        $check_payout_batch_status_url = $payouts_url . '/' . $payout_batch_id;
                        $create_payload = array(
                            'headers' => array(
                                'Accept' => 'application/json',
                                'Accept-Language' => 'en_US',
                                'Content-Type' => 'application/x-www-form-urlencoded',
                                'Authorization' => "Bearer $paypal_auth_token",
                            ),
                            'body' => 'grant_type=client_credentials',
                            // 'auth' => [$paypal_clientId, $paypal_secret, 'basic']
                        );

                        //let's check the batch status
                        $client = new \GuzzleHttp\Client();
                        $payout_batch_status_responses = $client->request(
                            'GET',
                            $check_payout_batch_status_url,
                            $create_payload
                        );

                        $confirm_payment_data = json_decode($payout_batch_status_responses->getBody(), true);
                        $statusCodes = $payout_batch_status_responses->getStatusCode();

                        if ($statusCodes == 200 && !empty($confirm_payment_data) && isset($confirm_payment_data['items'][0]) && in_array($confirm_payment_data['items'][0]['transaction_status'], array("SUCCESS"))) {
                            //update status of payment history
                            $payment_hostory = PaymentHistory::select('*')->where('id', $payment_history_id)->first();
                            $payment_hostory->transaction_id = $confirm_payment_data['items'][0]['transaction_id'];
                            $payment_hostory->transaction_status = $confirm_payment_data['items'][0]['transaction_status'];
                            $payment_hostory->paypal_response = json_encode($confirm_payment_data, true);
                            $payment_hostory->save();

                            //update status of user reward status
                            $user_reward = UserRewards::select('*')->where('id', $user_reward_id)->first();
                            $user_reward->reward_status = "SUCCESS";
                            $user_reward->save();

                            return $this->sendResponse(json_decode("{}"), Lang::get("campaign.campaign_coins_debit_redeem", array(), $this->selected_language), 200);
                        }
                    }

                    return $this->sendResponse(json_decode("{}"), Lang::get("campaign.campaign_coins_debit_redeem_but_in_processing", array(), $this->selected_language), 200);
                }
                return $this->sendError(Lang::get("campaign.paypal_authorization_failed", array(), $this->selected_language), null, 201);
            } catch (\Exception  $e) {
                return $this->sendError(Lang::get("common.something_went_wrong", array(), $this->selected_language), null, 500);
            }
        }

        return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), null, 201);
    }

    /**
     * @failed_transaction_entry - When Transaction failed function called
     * 
     * @param \Illuminate\Http\Request $UserCoins_Array ,$UserCoinsBalance_Array
     * @return \Illuminate\Http\JsonResponse
     */
    public function failed_transaction_entry($UserCoins_Array, $UserCoinsBalance_Array)
    {
        try {
            $Log = new Log;
            $Log->user_id = $UserCoins_Array['user_id'];
            $Log->action = "failed_transaction_entry";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $UserCoins = new UserCoins;
        $UserCoins->user_id = $UserCoins_Array['user_id'];
        $UserCoins->reward_id = $UserCoins_Array['reward_id'];
        $UserCoins->opening_balance = $UserCoins_Array['opening_balance'];
        $UserCoins->closing_balance = $UserCoins_Array['closing_balance'];
        $UserCoins->paypal_request = $UserCoins_Array['paypal_request'];
        $UserCoins->paypal_response = $UserCoins_Array['paypal_response'];
        $UserCoins->save();
        if ($UserCoins) {
            $UserCoinsBalance = new UserCoinsBalances;
            $UserCoinsBalance->user_id = $UserCoinsBalance_Array['user_id'];
            $UserCoinsBalance->coin_balance = $UserCoinsBalance_Array['coin_balance'];
            $UserCoinsBalance->save();
        }
    }
    /**
     * @AlreadyRedeemedRewards - This API is used for get user's already redeemed rewards.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function AlreadyRedeemedRewards(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "AlreadyRedeemedRewards";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $user_id = Auth::id();
        $ids = User::where('user_type', 2)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $UserCoins =  Rewards::select(
                'rewards.*',
                'user_coins.transaction_date'
            )
                ->join('user_coins', 'rewards.id', '=', 'user_coins.reward_id')
                ->where('user_id', $user_id)
                ->where('user_coins.reward_id', '!=', 'NULL')
                ->where('user_coins.debit', '!=', '0')
                ->get();
            $total_rewards = count($UserCoins);
            if ($total_rewards > 0) {
                return $this->sendResponse($UserCoins, Lang::get("common.success", array(), $this->selected_language), 200);
            } else {
                return $this->sendError(Lang::get("campaign.reward_not_found", array(), $this->selected_language), json_decode("[]"), 201);
            }
        } else {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), null, 401);
        }
    }
    /**
     * @AlreadyCompletedTask - This API is used for get user's already completed campaigns.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function AlreadyCompletedTask(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "AlreadyCompletedTask";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $user_id = Auth::id();
        $ids = User::where('user_type', 2)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $campaign_data =  Campaign::select(
                'campaigns.*',
                'user_coins.credit'
            )
                ->join('user_coins', 'campaigns.id', '=', 'user_coins.campaign_id')
                ->where('user_coins.user_id', $user_id)
                ->whereNotNull('user_coins.campaign_id')
                ->where('user_coins.credit', '!=', '0')
                ->get();
            if (!empty($campaign_data)) {
                foreach ($campaign_data as $campaign) {
                    if (!empty($campaign->campaign_image)) {
                        $Original = URL::to('/') . '/' . 'uploads/user_files/' . $campaign->campaign_image;
                        $campaign->campaign_image = $Original;
                    }
                    if (!empty($campaign->uploaded_video_url)) {
                        $Original_video = URL::to('/') . '/' . 'uploads/user_files/' . $campaign->uploaded_video_url;
                        $campaign->uploaded_video_url = $Original_video;
                    }
                    if (!empty($campaign->company_id)) {
                        $Company = Company::where('id', $campaign->company_id)->get();
                        $new = [];
                        $new = $Company;
                        $campaign->company_info = $new;
                    }
                }
                $data = $campaign_data;
                return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language), 200);
            } else {
                return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), json_decode("[]"), 201);
            }
        } else {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), null, 401);
        }
    }
    /**
     * @CampaignClicked - This API is used for How much time user clicked campaign button will save there count by campaign ID.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function CampaignClicked(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "CampaignClicked";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $user_id = Auth::id();
        $ids = User::where('user_type', 2)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $requestData = $request->json()->all();
            if (count($requestData) > 0) {
                $validator =  Validator::make($requestData, [
                    'brand_id' => 'required',
                    'campaign_id' => 'required'
                ]);
                if ($validator->fails()) {
                    $error = $validator->errors()->first();
                    return $this->sendError($error, null, 400);
                }
                $camp_ids = $requestData['campaign_id'];
                $campaignid = $this->encrypt_decrypt($camp_ids, 'decrypt');
                $CampaignClick = new CampaignClick;
                $CampaignClick->brand_id = $requestData['brand_id'];
                $CampaignClick->campaign_id = $campaignid;
                $CampaignClick->user_id = $user_id;
                $CampaignClick->is_clicked = '1';
                $CampaignClick->save();
                if (!empty($CampaignClick)) {
                    return $this->sendResponse($CampaignClick, Lang::get("campaign.campaign_clicked", array(), $this->selected_language), 200);
                } else {
                    return $this->sendError(Lang::get("campaign.campaign_clicked_failed", array(), $this->selected_language), null, 201);
                }
            } else {
                return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), null, 400);
            }
        } else {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), null, 401);
        }
    }
    /**
     * @CampaignStatistics - This API is used for get campaign statistics data.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function CampaignStatistics(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "CampaignStatistics";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $user_id = Auth::id();
        $ids = User::where('user_type', '!=', 2)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $requestData = $request->json()->all();
            if (count($requestData) > 0) {
                $validator =  Validator::make($requestData, [
                    'campaign_id' => 'required',
                    'statistics_type' =>  'required',
                    'from_date' => 'required',
                    'to_date' => 'required'
                ]);
                if ($validator->fails()) {
                    $error = $validator->errors()->first();
                    return $this->sendError($error, null, 400);
                }

                if ($requestData['statistics_type']  === 'week') {
                    $stastics = DB::select(DB::raw('SELECT DATE(inserted_at) AS dates,
                                                SUM(is_clicked - 1) AS is_clicked,
                                                SUM(is_completed - 1) AS is_completed 
                                                FROM campaign_clicks
                                                Where inserted_at >= "' . $requestData['from_date'] . '" and inserted_at <= "' . $requestData['to_date'] . '" 
                                                and campaign_id = "' . $requestData['campaign_id'] . '"
                                                GROUP BY DATE(inserted_at) ASC'));
                }
                if ($requestData['statistics_type']  === 'year') {
                    $stastics = DB::select(DB::raw('SELECT MONTH(inserted_at) AS months,
                    SUM(is_clicked - 1) AS is_clicked,
                    SUM(is_completed - 1) AS is_completed 
                    FROM campaign_clicks
                    Where inserted_at >= "' . $requestData['from_date'] . '" and inserted_at <= "' . $requestData['to_date'] . '" 
                    and campaign_id = "' . $requestData['campaign_id'] . '"
                    GROUP BY MONTH(inserted_at) ASC'));
                }
                if ($requestData['statistics_type']  === 'month') {
                    $stastics = DB::select(DB::raw('SELECT CONCAT("Week", FLOOR(((DAY(inserted_at) -1)/7)+1))  `month_week`,
                    SUM(is_clicked - 1) AS is_clicked,
                    SUM(is_completed - 1) AS is_completed 
                    FROM campaign_clicks
                    Where inserted_at >= "' . $requestData['from_date'] . '" and inserted_at <= "' . $requestData['to_date'] . '" 
                    and campaign_id = "' . $requestData['campaign_id'] . '"
                    GROUP BY month_week ORDER BY  MONTH(`inserted_at`),
                    `month_week`'));

                    // echo 'SELECT CONCAT(`week`, FLOOR(((DAY(inserted_at) -1)/7)+1))  `month_week`,
                    // SUM(is_clicked - 1) AS is_clicked,
                    // SUM(is_completed - 1) AS is_completed 
                    // FROM campaign_clicks
                    // Where inserted_at >= "' . $requestData['from_date'] . '" and inserted_at <= "' . $requestData['to_date'] . '" 
                    // and campaign_id = "' . $requestData['campaign_id'] . '"
                    // GROUP BY MONTH(inserted_at), month_week';
                }
                $campaign_target = Campaign::where('id', '=', $requestData['campaign_id'])
                    ->first();
                if (!empty($campaign_target)) {
                    $get_campaign_count = UserCoins::where('campaign_id', '=', $requestData['campaign_id'])
                        ->where('credit', '!=', '0')
                        ->count();
                    $percentage = ($get_campaign_count * 100) / $campaign_target->user_target;
                    $percentage = round($percentage, 2);
                    if ($percentage >= 100) {
                        $percentage = 100;
                    }
                    $campaign_debit_count = BrandWallet::where('campaign_id', $requestData['campaign_id'])->sum('debit');
                    $spend_euro = $campaign_debit_count;
                    $left_euro = $campaign_target->sub_total - $spend_euro;
                    $campaign_targets = (float)$campaign_target->sub_total;
                    $campaign_user_targets = (int)$campaign_target->user_target;
                    $left = [];
                    $left['total_budget'] =  $campaign_targets;
                    $left['total_left'] = $left_euro;
                    $left['total_spend'] = $spend_euro;
                    $new = [];
                    $new['total_target'] = $campaign_user_targets;
                    $new['total_archived'] = $get_campaign_count;
                    $new['target_percentage'] = $percentage;
                    $data['campaign_details'] = $campaign_target;
                    $data['target_details'] = $new;
                    $data['budget_details'] = $left;
                    if (!empty($stastics)) {
                        $data['stastics'] = $stastics;
                    }
                    if (!empty($data)) {
                        return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language), 200);
                    } else {
                        return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), json_decode("{}"), 201);
                    }
                } else {
                    return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), json_decode("{}"), 201);
                }
            } else {
                return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), null, 400);
            }
        } else {
            return $this->sendError(Lang::get("common.unauthorized", array(), $this->selected_language), null, 401);
        }
    }
    /**
     * @GetLeadCampaign - This API is used for get list of lead campaigns.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetLeadCampaign(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetLeadCampaign";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $user_id = Auth::id();
        $ids = User::where('user_type', 1)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $user = User::find($user_id);
            if (!empty($user)) {
                $company = Company::where('user_id', $user->id)->first();
                if (!empty($company)) {
                    $approval = "APPROVED";
                    $admin_is_approved = "APPROVED";
                    $campaign_type = "lead_target";
                    $campaigns = Campaign::select(
                        'id',
                        'campaign_name',
                        'campaign_type',
                        'campaign_type_name',
                        'start_date',
                        'end_date',
                        'user_target',
                        'total_budget',
                        'created_at'
                    )->where('company_id', $company->id)
                        ->where('campaign_type', $campaign_type)
                        ->where('campaign_status', $approval)
                        ->where('is_approved', $admin_is_approved)
                        ->where('active', 1)
                        ->get();
                    $campaign_count = count($campaigns);
                    if ($campaign_count) {
                        $data = [];
                        foreach ($campaigns as $campaign) {
                            if (!empty($campaign->campaign_image)) {
                                $Original = URL::to('/') . '/' . 'uploads/user_files/' . $campaign->campaign_image;
                                $campaign->campaign_image = $Original;
                            }
                            // if (!empty($campaign->company_id)) {
                            //     $Company = Company::where('id', $campaign->company_id)->get();
                            //     $new = [];
                            //     $new = $Company;
                            //     $campaign->company_info = $new;
                            // }
                        }
                        $data = $campaigns;
                        return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language), 200);
                    } else {
                        return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), json_decode("{}"), 201);
                    }
                } else {
                    return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 201);
                }
            } else {
                return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 403);
            }
        } else {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 401);
        }
    }
    /**
     * @GetUserbyLeadCampaign - This API is used for get lead users by campaign ID.
     * 
     * @param \Illuminate\Http\Request $request
     * @param {Number} $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetUserbyLeadCampaign(Request $request, $id)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetUserbyLeadCampaign";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $user_id = Auth::id();
        $ids = User::where('user_type', 1)->where('id', $user_id)->first();
        if (!empty($ids) and !empty($id)) {
            $user = User::find($user_id);
            $campaign = Campaign::select('id', 'company_id', 'campaign_name', 'campaign_type', 'campaign_type_name')->where('id', $id)->first();
            if (!empty($campaign)) {
                $data['campaign_details'] = $campaign;
                $data['user_details'] = NULL;
            }
            if (!empty($user)) {
                $UserCoins = UserCoins::where('campaign_id', $id)
                    ->where('credit', '!=', "0")->get();
                $total_UserCoins = count($UserCoins);
                if (!empty($total_UserCoins)) {
                    foreach ($UserCoins as $UserCoin) {
                        $ids_users[] = $UserCoin->user_id;
                    }
                    if (!empty($ids_users)) {
                        $users_completed =  User::select(
                            'users.first_name',
                            'users.last_name',
                            'users.email',
                            'users.dob',
                            'users.phone',
                            'users.gender',
                            'users.city',
                            'countries.country_name',
                            'states.state_name'
                        )
                            ->join('countries', 'users.country', '=', 'countries.id')
                            ->join('states', 'users.state', '=', 'states.id')
                            ->wherein('users.id', $ids_users)
                            ->get();
                        $total_users = count($users_completed);
                        if ($total_users > 0) {
                            $data['user_details'] = $users_completed;
                        } else {
                            $data['user_details'] = NULL;
                        }
                        return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language), 200);
                    } else {
                        return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language), 200);
                    }
                } else {
                    return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language), 200);
                }
            } else {
                return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language), 200);
            }
        } else {
            return $this->sendError(Lang::get("common.unauthorized", array(), $this->selected_language), json_decode("{}"), 401);
        }
    }
    /**
     * @TwitterAuth - This API is used for twitter authentication.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function TwitterAuth(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "TwitterAuth";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $user_id = Auth::id();
        $ids = User::where('user_type', 2)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $uri = 'https://api.twitter.com/oauth/request_token';
            $stack = HandlerStack::create();
            $middleware = new Oauth1([
                'consumer_key'    => env("TWITTER_CLIENT_ID", null),
                'consumer_secret' => env("TWITTER_CLIENT_SECRET", null)
            ]);
            $stack->push($middleware);
            $client = new Client([
                'handler' => $stack
            ]);
            try {
                $response = $client->request(
                    'GET',
                    $uri,
                    [
                        'auth' => 'oauth'
                    ]
                );
                $params = (string)$response->getBody();
                parse_str($params, $out_put);
                ///dd($out_put);
                $second_url['url'] = "https://api.twitter.com/oauth/authorize?oauth_token={$out_put['oauth_token']}&oauth_token_secret={$out_put['oauth_token_secret']}";
                return $this->sendResponse($second_url, Lang::get("common.success", array(), $this->selected_language), 200);
            } catch (ClientException $e) {
                if ($e->getResponse()->getStatusCode() == 429) {
                    // $this->markTestIncomplete(Lang::get("common.something_went_wrong", array(), $this->selected_language));
                    $this->sendError(Lang::get("common.something_went_wrong", array(), $this->selected_language), null, 500);
                } else {
                    // throw $e;
                }

                $this->sendError(Lang::get("common.something_went_wrong", array(), $this->selected_language), null, 500);
            }
        } else {
            $this->sendError(Lang::get("common.unauthorized", array(), $this->selected_language), null, 401);
        }
    }
    /**
     * @TwitterSecondApi - This API is used for twitter follow of specific username.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function TwitterSecondApi(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "TwitterSecondApi";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $user_id = Auth::id();
        $ids = User::where('user_type', 2)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $requestData = $request->json()->all();
            if (count($requestData) > 0) {
                $validator =  Validator::make($requestData, [
                    'campaign_id' => 'required',
                    'oauth_token' => 'required',
                    'oauth_verifier' => 'required',
                    'target_screen_name' => 'required'
                ]);
                if ($validator->fails()) {
                    $error = $validator->errors()->first();
                    return $this->sendError($error, null, 400);
                }
                $new = [];
                $new['oauth_token'] =  $requestData['oauth_token'];
                $new['oauth_verifier'] =  $requestData['oauth_verifier'];
                if (!empty($new)) {
                    $client = new \GuzzleHttp\Client();
                    $third_url = 'https://api.twitter.com/oauth/access_token';
                    $response_third_api = $client->request(
                        'POST',
                        $third_url,
                        [
                            RequestOptions::QUERY => array_merge(
                                $new
                            )
                        ]
                    );
                    $params_n = (string)$response_third_api->getBody();
                    parse_str($params_n, $out_puts);
                    if (!empty($out_puts)) {
                        $third_urls = "https://api.twitter.com/2/users/by/username/{$requestData['target_screen_name']}";
                        $token = env("TWITTER_BEARER_TOKEN", null);
                        $client = new \GuzzleHttp\Client();
                        $headers = [
                            'Authorization' => 'Bearer ' . $token,
                            'Accept'        => 'application/json',
                        ];
                        $response = $client->request('GET', $third_urls, [
                            'headers' => $headers
                        ]);
                        $params = (string)$response->getBody();
                        $params_new_resp = json_decode($params);
                        $target_user_id = $params_new_resp->data->id;
                        if (!empty($target_user_id)) {
                            $forth_uri = 'https://api.twitter.com/2/users/' . $out_puts['user_id'] . '/following';
                            $stack = HandlerStack::create();
                            $middleware = new Oauth1([
                                'consumer_key'    => env("TWITTER_CLIENT_ID", null),
                                'consumer_secret' => env("TWITTER_CLIENT_SECRET", null),
                                'token'           => $out_puts['oauth_token'],
                                'token_secret'    => $out_puts['oauth_token_secret']
                            ]);
                            $stack->push($middleware);
                            $client = new Client([
                                'handler' => $stack
                            ]);
                            $rowDatas = '{ "target_user_id": "' . $target_user_id . '" }';
                            try {
                                $response = $client->request(
                                    'POST',
                                    $forth_uri,
                                    [
                                        'headers' =>
                                        [
                                            'Content-Type' => 'application/json'
                                        ],
                                        'body' => $rowDatas,
                                        'auth' => 'oauth',
                                    ]
                                );
                                $params = (string)$response->getBody();
                                $params_new_resp = json_decode($params);

                                $camp_ids = $requestData['campaign_id'];
                                $campaignid = $this->encrypt_decrypt($camp_ids, 'decrypt');
                                // store twitter data
                                $twitterFollow = new TwitterFollows;
                                $twitterFollow->campaign_id = $campaignid;
                                $twitterFollow->user_id = $user_id;
                                $twitterFollow->brand_twitter_account = $requestData['target_screen_name'];
                                $twitterFollow->user_twitter_id = $out_puts['user_id'];
                                $twitterFollow->user_twitter_account = $out_puts['screen_name'];
                                $twitterFollow->save();
                                return $this->sendResponse($params_new_resp, Lang::get("common.success", array(), $this->selected_language), 200);
                            } catch (ClientException $e) {
                                if ($e->getResponse()->getStatusCode() == 429) {
                                    return $this->sendError(Lang::get("common.please_try_again_later", array(), $this->selected_language), json_decode("{}"), 201);
                                    // $this->markTestIncomplete(Lang::get("common.something_went_wrong", array(), $this->selected_language));
                                } else {
                                    throw $e;
                                }
                                return $this->sendError(Lang::get("common.something_went_wrong", array(), $this->selected_language), null, 500);
                            }
                        } else {
                            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 201);
                        }
                    } else {
                        return $this->sendError(Lang::get("common.something_went_wrong", array(), $this->selected_language), null, 201);
                    }
                } else {
                    return $this->sendError(Lang::get("campaign.unauthorized_twitter", array(), $this->selected_language), json_decode("{}"), 401);
                }
            } else {
                return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), json_decode("{}"), 201);
            }
        } else {
            return $this->sendError(Lang::get("common.unauthorized", array(), $this->selected_language), json_decode("{}"), 401);
        }
    }
    /**
     * @GetMaxCacByCampaignType - This API is used for get cac by campaign type.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetMaxCacByCampaignType(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetMaxCacByCampaignType";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $user_id = Auth::id();
        $ids = User::where('user_type', 1)->where('id', $user_id)->first();
        if (!empty($ids)) {
            $requestData = $request->json()->all();
            if (count($requestData) > 0) {
                $validator =  Validator::make($requestData, [
                    'campaign_type' => 'required'
                ]);
                if ($validator->fails()) {
                    $error = $validator->errors()->first();
                    return $this->sendError($error, null, 400);
                }
                $your_Valu = $requestData['campaign_type'];
                $is_approved = "APPROVED";
                if (!empty($your_Valu)) {
                    $max_cac = Campaign::where("campaign_type", $your_Valu)
                        ->where("is_approved", $is_approved)
                        ->max('cac');
                    $data[]['max_cac'] = $max_cac;
                }
                return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language), 200);
            } else {
                return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), json_decode("{}"), 201);
            }
        } else {
            return $this->sendError(Lang::get("common.unauthorized", array(), $this->selected_language), json_decode("{}"), 401);
        }
    }
    /**
     * @CalculateCoinUserTarget - This API is used for calculating coins and user target.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function CalculateCoinUserTarget($total_budget, $cac, $campaign)
    {
        if (!empty($total_budget) and !empty($cac) and !empty($campaign)) {
            $coins = (($cac / 2) * 166.3860);
            $user_target =  floor($total_budget / $cac);
            $campaign->coins = $coins;
            $campaign->user_target = $user_target;
        }
    }

    /**
     * @PaymentPayoutStatus - This API is used for paypal webhook will be call by paypal
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function PaymentPayoutStatus(Request $request)
    {
        $paymentData = $request->json()->all();

        //creating log file of webhook requests
        $file = 'payout-log-' . date('Ymd') . '.txt';
        $txt = "\n\n";
        $txt .= "============================";
        $txt .= "\n Date : " . date('Y-m-d H:i:s');
        $txt .= "\n Content : " . json_encode($paymentData, true);
        $path = realpath(dirname(__FILE__));
        file_put_contents($path . '/' . $file, $txt . PHP_EOL, FILE_APPEND | LOCK_EX);

        //check paypal webhook request data and match with our payment history
        if (!empty($paymentData) && isset($paymentData['resource_type']) && $paymentData['resource_type'] === "payouts_item") {

            //get payout item data
            $payout_item = $paymentData['resource'];

            //get payment history record with matching payout batch id
            $payment_history = PaymentHistory::select('*')->where('paypal_reference_number', $payout_item['payout_batch_id'])->first();

            //process only not success
            if ($payment_history->transaction_status != "SUCCESS") {
                $payment_history_id = $payment_history->id;

                //update status of payment history
                $payment_history->transaction_id = $payout_item['transaction_id'];
                $payment_history->transaction_status = $payout_item['transaction_status'];
                $payment_history->paypal_response = json_encode($paymentData, true);
                $payment_history->save();


                $reward_status = "";
                $is_transaction_cancelled = false;
                if (in_array($payout_item['transaction_status'], array("FAILED", "RETURNED", "REFUNDED", "REVERSED"))) {
                    $reward_status = "FAILED";
                    $is_transaction_cancelled = true;
                } elseif ($payout_item['transaction_status'] === "SUCCESS") {
                    $reward_status = "SUCCESS";
                }

                if (!empty($reward_status)) {

                    //update status of user reward status
                    $user_reward = UserRewards::select('*')->where('payment_history_id', $payment_history_id)->first();

                    //update reward status
                    $user_reward->reward_status = $reward_status;
                    $user_reward->save();

                    //credit coins if its failed
                    if ($is_transaction_cancelled) {
                        $credit = UserCoins::creditCoins(array(
                            "user_id" => $user_reward->user_id,
                            "credit" => $user_reward->redeem_coins,
                            "comments" => "Credited coins due to paypal reward failed",
                        ));
                    }
                }
            }
        }

        return $this->sendResponse(null, Lang::get("common.success", array(), $this->selected_language), 200);
    }

    public function QuestionsCampaign(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "QuestionsCampaign";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }

        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $user_id = Auth::id();
            $ids = User::where('user_type', 1)->where('id', $user_id)->first();
            if (!empty($ids)) {
                $userDetails = Company::where('user_id', $user_id)->first();
                if (!empty($userDetails->id)) {
                    $validator =  Validator::make($requestData, [
                        'campaign_name' => 'required',
                        'start_date' => 'required',
                        'end_date' => 'required',
                        'product_information' => 'required',
                        'cac' => 'required',
                        'sub_total' => 'required',
                        'tax_value' => 'required',
                        'total_budget' => 'required',
                        'coins' => 'required',
                        'user_target' => 'required',
                        'campaign_image' => 'required'
                    ]);
                    if ($validator->fails()) {
                        $error = $validator->errors()->first();
                        return $this->sendError($error, null, 400);
                    }
                    $goal_of_campaign = "";
                    $campaign_type_name = "";
                    $campaign_name = $requestData['campaign_name'];
                    $campaign_type = $requestData['campaign_type'];
                    if (!empty($requestData['goal_of_campaign'])) {
                        $goal_of_campaign = $requestData['goal_of_campaign'];
                    }
                    $start_date = $requestData['start_date'];
                    $end_date = $requestData['end_date'];
                    $product_information = $requestData['product_information'];
                    $cac = $requestData['cac'];
                    $sub_total = $requestData['sub_total'];
                    $tax_value = $requestData['tax_value'];
                    $total_budget = $requestData['total_budget'];
                    $coins = $requestData['coins'];
                    $user_target = $requestData['user_target'];
                    $campaign_image = $requestData['campaign_image'];
                    if (!empty($requestData['country'])) {
                        $country = $requestData['country'];
                    }
                    if (!empty($requestData['start_age'])) {
                        $start_age = $requestData['start_age'];
                    }
                    if (!empty($requestData['end_age'])) {
                        $end_age = $requestData['end_age'];
                    }
                    if (!empty($requestData['gender'])) {
                        $gender = $requestData['gender'];
                    }
                    if (!empty($requestData['campaign_type_name'])) {
                        $campaign_type_name = $requestData['campaign_type_name'];
                    }
                    $campaign = new Campaign;
                    $campaign->company_id = $userDetails->id;
                    $campaign->campaign_name = $campaign_name;
                    $campaign->campaign_type = $campaign_type;
                    if (!empty($campaign_type_name)) {
                        $campaign->campaign_type_name = $campaign_type_name;
                    }
                    if (!empty($goal_of_campaign)) {
                        $campaign->goal_of_campaign = $goal_of_campaign;
                    }
                    $campaign->start_date = date("Y-m-d", strtotime($start_date));
                    $campaign->end_date = date("Y-m-d", strtotime($end_date));
                    $campaign->product_information = $product_information;
                    $campaign->cac = $cac;
                    $campaign->sub_total = $sub_total;
                    $campaign->tax_value = $tax_value;
                    $campaign->total_budget = $total_budget;

                    //calling CalculateCoinUserTarget function
                    if (!empty($sub_total) and !empty($cac)) {
                        $this->CalculateCoinUserTarget($sub_total, $cac, $campaign);
                    }

                    if (!empty($country)) {
                        $campaign->country = $country;
                    }
                    if (!empty($start_age)) {
                        $campaign->start_age = $start_age;
                    }
                    if (!empty($end_age)) {
                        $campaign->end_age = $end_age;
                    }
                    if (!empty($gender)) {
                        $campaign->gender = $gender;
                    }
                    if (!empty($campaign_image)) {
                        $this->image_upload($campaign_image, $campaign);
                    }
                    $campaign->save();
                    if (!empty($campaign)) {
                        $Campaign_Details = Campaign::find($campaign->id);
                        if (!empty($Campaign_Details->campaign_image)) {
                            $Original = URL::to('/') . '/' . 'uploads/user_files/' . $Campaign_Details->campaign_image;
                            $Campaign_Details['campaign_image'] = $Original;
                        }
                        return $this->sendResponse($Campaign_Details, Lang::get("common.success", array(), $this->selected_language), 200);
                    }
                } else {
                    return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 201);
                }
            } else {
                return $this->sendError(Lang::get("common.unauthorized", array(), $this->selected_language), json_decode("{}"), 401);
            }
        }
    }

    public function load_question_types()
    {
        $question_types = QuestionType::all();
        if (!empty($question_types)) {
            return $this->sendResponse($question_types, Lang::get("common.success", array(), $this->selected_language), 200);
        } else {
            return $this->sendError(Lang::get("common.question_type_not_found", array(), $this->selected_language), null, 201);
        }
    }

    public function AddCampaignFormQuestions(Request $request)
    {
        $requestData = $request->json()->all();
        // Add validations
        if (count($requestData) > 0) {
            foreach ($requestData as $questionDataa) {
                $validator =  Validator::make($questionDataa, [
                    'question_type_id' => 'required',
                    'campaign_form_id' => 'required',
                    'question_text' => 'required'
                ]);
                if ($validator->fails()) {
                    $error = $validator->errors()->first();
                    return $this->sendError($error, null, 400);
                }
                $questionData = new CampaignFormQuestion;
                $questionData->question_type_id  = $questionDataa['question_type_id'];
                $questionData->campaign_form_id  = $questionDataa['campaign_form_id'];
                $questionData->question_text  = $questionDataa['question_text'];
                $questionData->answer_text = !empty($questionDataa['answer_text']) ? json_encode($questionDataa['answer_text']) : NULL;
                $questionData->save();
            }
            return $this->sendResponse(json_decode("{}"), Lang::get("common.success", array(), $this->selected_language), 200);
        }
    }

    public function AddUserFormAnswers(Request $request)
    {
        $requestData = $request->json()->all();
        // dd($requestData);
        $user_id = Auth::id();

        $camp_ids = $requestData['campaign_id'];
        $campaignId = $this->encrypt_decrypt($camp_ids, 'decrypt');

        if (count($requestData) > 0) {
            // $request->validate([
            //     'camapign_form_id' => 'required',
            //     'campaign_id' => 'required',
            //     'user_id' => 'required'
            // ]);

            // check if the task is already performed
            $check_already_performed_task = UserFormAnswer::where('campaign_id', $campaignId)->where('user_id',  $user_id)->first();
            //   dd($check_already_performed_task);

            if (!$check_already_performed_task) {
                foreach ($requestData['question_data'] as $answerDataa) {
                    //dd($answerDataa);
                    $validator =  Validator::make($answerDataa, [
                        'question_id' => 'required',
                        'answer' => 'required',
                    ]);
                    if ($validator->fails()) {
                        $error = $validator->errors()->first();
                        return $this->sendError($error, null, 400);
                    }
                    $answerData = new UserFormAnswer;
                    $answerData->camapign_form_id  = $requestData['camapign_form_id'];
                    $answerData->campaign_id  = $campaignId;
                    $answerData->question_id  = $answerDataa['question_id'];
                    $answerData->answer = $answerDataa['answer'];
                    $answerData->user_id =  Auth::id();
                    $answerData->save();
                }
                return $this->sendResponse(json_decode("{}"), Lang::get("common.success", array(), $this->selected_language), 200);
            } else {
                return $this->sendError(Lang::get("campaign.campaign_task_already_completed", array(), $this->selected_language), null, 201);
            }
        }
    }

    public function addQuestionsForm(Request $request)
    {
        $requestData = $request->json()->all();

        // $camp_ids = $requestData['campaign_id'];
        // $campaignId = $this->encrypt_decrypt($camp_ids, 'decrypt');

        if (count($requestData) > 0) {
            $validator =  Validator::make($requestData, [
                'campaign_id' => 'required',
                'form_name' => 'required',
                'description' => 'required'
            ]);
            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            }

            $campaign_form_data = new CampaignFormData;
            $campaign_form_data->campaign_id = $requestData['campaign_id'];
            $campaign_form_data->form_name = $requestData['form_name'];
            $campaign_form_data->description = $requestData['description'];
            $campaign_form_data->save();
            return $this->sendResponse($campaign_form_data, Lang::get("common.success", array(), $this->selected_language), 200);
        }
    }

    public function getFormQuestions(Request $request)
    {
        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $validator =  Validator::make($requestData, [
                'campaign_id' => 'required'
            ]);

            $camp_ids = $requestData['campaign_id'];
            $campaignid = $this->encrypt_decrypt($camp_ids, 'decrypt');

            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            }
            $form_questions = CampaignFormData::where('campaign_id', $campaignid)->with('questions')->first();
            if (!empty($form_questions)) {
                $form_questions = $form_questions->toArray();
            }

            return $this->sendResponse($form_questions, Lang::get("common.success", array(), $this->selected_language), 200);
        }
    }

    public function getFormQuestionsReport(Request $request)
    {
        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $validator =  Validator::make($requestData, [
                'campaign_id' => 'required'
            ]);

            $campaignid = $requestData['campaign_id'];

            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            }

            $form_questions = CampaignFormData::where('campaign_id', $campaignid)->whereHas('questions')->with('questions')->first()->toArray();

            foreach ($form_questions['questions'] as $key => $questions) {
                $options_with_percentage = [];
                if (count($questions['options']) > 0) {
                    foreach ($questions['options'] as $option) {
                        $number_of_users_answered_this_question = UserFormAnswer::where('question_id', $questions['id'])->count();
                        $number_of_users_selected_this_option = UserFormAnswer::where('question_id', $questions['id'])->where('answer', $option)->count();
                        $number_of_users_selected_this_option = ($number_of_users_selected_this_option > 0) ? $number_of_users_selected_this_option : 0;
                        $percentage = ($number_of_users_answered_this_question > 0 && $number_of_users_selected_this_option > 0) ? ($number_of_users_selected_this_option * 100) / $number_of_users_answered_this_question : 0;
                        if (!array_key_exists($option, $options_with_percentage)) {
                            $options_with_percentage[$option] = ['option_value' => $option, 'percentage' => $percentage];
                        }
                    }
                }
                if (($questions['question_type_id']) === 5) {
                    $number_of_users_answered_this_true_false_question = UserFormAnswer::where('question_id', $questions['id'])->count();
                    $number_of_users_selected_true_option = UserFormAnswer::where('question_id', $questions['id'])->where('answer', 'true')->count();
                    $number_of_users_selected_false_option = UserFormAnswer::where('question_id', $questions['id'])->where('answer', 'false')->count();
                    $true_percentage = ($number_of_users_answered_this_true_false_question > 0 && $number_of_users_selected_true_option > 0) ? ($number_of_users_selected_true_option * 100) / $number_of_users_answered_this_true_false_question : 0;
                    $false_percentage = ($number_of_users_answered_this_true_false_question > 0 && $number_of_users_selected_false_option > 0) ? ($number_of_users_selected_false_option * 100) / $number_of_users_answered_this_true_false_question : 0;
                    $options_with_percentage[0] = ['option_value' => 'true', 'percentage' => $true_percentage];
                    $options_with_percentage[1] = ['option_value' => 'false', 'percentage' => $false_percentage];
                    //$options_with_percentage[$option] = [];
                }
                $form_questions['questions'][$key]['options_with_percentage'] = array_values($options_with_percentage);
            }
            // $form_questions = $form_questions->toArray();
            return $this->sendResponse($form_questions, Lang::get("common.success", array(), $this->selected_language), 200);
        }
    }

    public function getQuestionAnswers(Request $request)
    {
        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $validator =  Validator::make($requestData, [
                'question_id' => 'required'
            ]);

            $questionId = $requestData['question_id'];

            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            }

            $form_answers = UserFormAnswer::where('question_id', $questionId)->get();
            $get_question = CampaignFormQuestion::where('id', $questionId)->first();
            $get_question_text = $get_question['question_text'];


            $data = array(
                "question_test" => $get_question_text,
                "form_answers" => $form_answers
            );

            return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language), 200);
        }
    }

    public function getFormCampaignDetailReport(Request $request, $campaign_id)
    {
        // $campaign_id = 417;
        // $campaign_id = 375;

        $requestData = $request->json()->all();
        $form_questions = CampaignFormData::where('campaign_id', $campaign_id)->with('questions', 'questions.user_answers')->first();

        if (!empty($form_questions)) {
            $form_questions = $form_questions->toArray();
        }
        $allQuestions = [];
        foreach ($form_questions['questions'] as $key => $question) {
            $allQuestions[$question['id']] = ['text' => $question['question_text'], 'answers' => $question['user_answers']];
        }
        $final_que_data = [0 => 'Id'];
        $final_ans_data = [];
        foreach ($allQuestions as $questionId => $question) {
            if (count($question['answers']) >= 0) {
                foreach ($question['answers'] as $key1 => $answer) {
                    $userId = $answer['user_id'];
                    if (!array_key_exists($userId, $final_ans_data)) {
                        $final_ans_data[$userId] = [0 => count($final_ans_data) + 1, $questionId => ''];
                    }
                    if ($answer['question_id'] === $questionId) {
                        $final_ans_data[$userId][$questionId] = $answer['answer'];
                    }
                }
            }
            $final_que_data[$questionId] = $question['text'];
        }
        $final_csv_data = [];
        $final_csv_data[0][] = $final_que_data;

        $final_csv_data = array_merge($final_csv_data[0], $final_ans_data);

        return $this->sendResponse($final_csv_data, Lang::get("common.success", array(), $this->selected_language), 200);
    }

    public function getFormCampaignDetailReportFromAdmin(Request $request, $campaign_id)
    {
        $requestData = $request->json()->all();
        $form_questions = CampaignFormData::where('campaign_id', $campaign_id)->with('questions', 'questions.user_answers')->first();

        if (!empty($form_questions)) {
            $form_questions = $form_questions->toArray();
        }
        $allQuestions = [];
        foreach ($form_questions['questions'] as $key => $question) {
            $allQuestions[$question['id']] = ['text' => $question['question_text'], 'user_id' => '', 'answers' => $question['user_answers']];
        }
        $final_que_data = [0 => 'Id', 'user_id' => 'userId'];
        $final_ans_data = [];
        foreach ($allQuestions as $questionId => $question) {
            if (count($question['answers']) >= 0) {
                foreach ($question['answers'] as $key1 => $answer) {
                    $userId = $answer['user_id'];
                    if (!array_key_exists($userId, $final_ans_data)) {
                        $final_ans_data[$userId] = [0 => count($final_ans_data) + 1, 'user_id' => '', $questionId => ''];
                    }
                    if ($answer['question_id'] === $questionId) {
                        $final_ans_data[$userId]['user_id'] = $userId;
                        $final_ans_data[$userId][$questionId] = $answer['answer'];
                    }
                }
            }
            $final_que_data[$questionId] = $question['text'];
        }
        $final_csv_data = [];
        $final_csv_data[0][] = $final_que_data;

        $final_csv_data = array_merge($final_csv_data[0], $final_ans_data);

        return $this->sendResponse($final_csv_data, Lang::get("common.success", array(), $this->selected_language), 200);
    }
}
