<?php

namespace App\Http\Controllers\API\V1;

use App\BrandWallet;
use App\Campaign;
use App\Http\Controllers\Controller;
use App\MetricsLink;
use App\UserCoins;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Crypt;

class MetricsController extends Controller
{
    public function generateRandomLink(Request $request)
    {
        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $validator =  Validator::make($requestData, [
                'campaign_id' => 'required'
            ]);
            // check if the link is already created for the campaign id
            $get_campaign_link = MetricsLink::where('campaign_id', $requestData['campaign_id'])->first();

            if (!empty($get_campaign_link)) {

                $metrics_link_data = array(
                    'link' => $get_campaign_link['campaign_sharing_code'],
                    'password' => $get_campaign_link['campaign_sharing_password'],
                    'is_link_already_generated' => true
                );

                return $this->sendResponse($metrics_link_data, Lang::get("common.success", array(), $this->selected_language), 200);
            } else {
                $randomPassword = str_shuffle('abcdefghjklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ234567890!$%^&!$%^&');
                $password = substr($randomPassword, 0, 10);
                $randomStringId = Str::random(20);

                $metrics_link_data = array(
                    'link' => $randomStringId,
                    'password' => $password,
                    'is_link_already_generated' => false
                );

                return $this->sendResponse($metrics_link_data, Lang::get("common.success", array(), $this->selected_language), 200);
            }
        } else {
            return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), null, 400);
        }
    }

    public function saveMetricsLink(Request $request)
    {
        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $validator =  Validator::make($requestData, [
                'campaign_id' => 'required',
                'link' => 'required',
                'password' => 'required'
            ]);

            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            }

            $metrics_link = new MetricsLink();
            $metrics_link->campaign_sharing_code = $requestData['link'];
            $metrics_link->campaign_sharing_password = base64_encode($requestData['password']);
            $metrics_link->campaign_id = $requestData['campaign_id'];
            $metrics_link->save();
            if (!empty($metrics_link)) {
                return $this->sendResponse($metrics_link, Lang::get("common.success", array(), $this->selected_language), 200);
            }
        } else {
            return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), null, 400);
        }
    }

    public function ViewCampaignStatistics(Request $request)
    {
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
    }

    public function checkCampaignLink(Request $request)
    {
        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $validator =  Validator::make($requestData, [
                'link' => 'required',
            ]);
            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            }

            $get_campaign_data_from_link = MetricsLink::select('campaign_sharing_code', 'campaign_id')->where('campaign_sharing_code', $requestData['link'])->first();

            if (!empty($get_campaign_data_from_link)) {
                return $this->sendResponse($get_campaign_data_from_link, Lang::get("common.success", array(), $this->selected_language), 200);
            } else {
                return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), json_decode("{}"), 201);
            }
        } else {
            return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), null, 400);
        }
    }

    public function checkCampaignLinkAndPassword(Request $request)
    {

        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $validator =  Validator::make($requestData, [
                'link' => 'required',
                'password' => 'required'
            ]);
            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            }
            $check_camapign_link_and_password = MetricsLink::where('campaign_sharing_code', $requestData['link'])->where('campaign_sharing_password', base64_encode($requestData['password']))->first();

            if (!empty($check_camapign_link_and_password)) {
                return $this->sendResponse($check_camapign_link_and_password, Lang::get("common.success", array(), $this->selected_language), 200);
            } else {
                return $this->sendError(Lang::get("common.invalid_password", array(), $this->selected_language), json_decode("{}"), 201);
            }
        } else {
            return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), null, 400);
        }
    }
}
