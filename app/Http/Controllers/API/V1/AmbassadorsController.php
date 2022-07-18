<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\MetricsLink;
use App\ReferralStreamersName;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\DB;

class AmbassadorsController extends Controller
{
    public function checkAmbassadorLink(Request $request)
    {
        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $validator =  Validator::make($requestData, [
                'streamer_name' => 'required',
            ]);
            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            }

            $get_streamer_data_from_link = ReferralStreamersName::select('streamer_name')->where('streamer_name', $requestData['streamer_name'])->first();

            if (!empty($get_streamer_data_from_link)) {
                return $this->sendResponse($get_streamer_data_from_link, Lang::get("common.success", array(), $this->selected_language), 200);
            } else {
                return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), json_decode("{}"), 201);
            }
        } else {
            return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), null, 400);
        }
    }

    public function checkAmbassadorLinkAndPassword(Request $request)
    {
        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $validator =  Validator::make($requestData, [
                'streamer_name' => 'required',
                'password' => 'required'
            ]);
            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            }
            $check_streamer_link_and_password = ReferralStreamersName::where('streamer_name', $requestData['streamer_name'])->where('password', base64_encode($requestData['password']))->first();

            if (!empty($check_streamer_link_and_password)) {
                return $this->sendResponse(array(), Lang::get("common.success", array(), $this->selected_language), 200);
            } else {
                return $this->sendError(Lang::get("common.invalid_password", array(), $this->selected_language), json_decode("{}"), 201);
            }
        } else {
            return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), null, 400);
        }
    }

    public function viewStreamerPerformanceStatistics(Request $request)
    {
        $requestData = $request->json()->all();
        if (count($requestData) > 0) {
            $validator =  Validator::make($requestData, [
                'streamer_name' => 'required',
                'statistics_type' =>  'required',
                'from_date' => 'required',
                'to_date' => 'required'
            ]);
            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return $this->sendError($error, null, 400);
            }
            if ($requestData['statistics_type']  === 'week') {
                $stastics_own_subs = DB::select(DB::raw('select  DATE(created_at) AS dates, COUNT(id) as own_subs from `user_rewards` where reward_id = 1  and reward_status = "SUCCESS" and description = "' . $requestData['streamer_name'] . '" AND created_at >= "' . $requestData['from_date'] . '" AND created_at <= "' . $requestData['to_date'] . '" AND user_id IN (select user_id from referrals_data WHERE referral_id = "' . $requestData['streamer_name'] . '") GROUP BY DATE(created_at) ASC'));
                $stastics_other_subs = DB::select(DB::raw('select DATE(created_at) AS dates, COUNT(id) as other_subs from `user_rewards` where reward_id = 1  and reward_status = "SUCCESS" and description != "' . $requestData['streamer_name'] . '" AND created_at >= "' . $requestData['from_date'] . '" AND created_at <= "' . $requestData['to_date'] . '" AND user_id IN (select user_id from referrals_data WHERE referral_id = "' . $requestData['streamer_name'] . '") GROUP BY DATE(created_at) ASC'));
                $stastics_other_rewards = DB::select(DB::raw('select DATE(created_at) AS dates, COUNT(id) as other_rewards from `user_rewards` where reward_id != 1  and reward_status = "SUCCESS" AND created_at >= "' . $requestData['from_date'] . '" AND created_at <= "' . $requestData['to_date'] . '" AND user_id IN (select user_id from referrals_data WHERE referral_id = "' . $requestData['streamer_name'] . '") GROUP BY DATE(created_at) ASC'));
                $statistics = array_merge($stastics_own_subs, $stastics_other_subs, $stastics_other_rewards);
                $months = array_unique(array_column($statistics, 'month'));
                $statistics_data = [];
                foreach ($statistics as $key => $values) {
                    $values = (array)$values;

                    if (!array_key_exists($values['dates'], $statistics_data)) {
                        $statistics_data[$values['dates']] = array('dates' => $values['dates'], 'own_subs' => 0, 'other_subs' => 0, 'other_rewards' => 0);
                    }

                    $statistics_data[$values['dates']]['own_subs'] += isset($values['own_subs']) ? $values['own_subs'] : 0;
                    $statistics_data[$values['dates']]['other_subs'] += isset($values['other_subs']) ? $values['other_subs'] : 0;
                    $statistics_data[$values['dates']]['other_rewards'] += isset($values['other_rewards']) ? $values['other_rewards'] : 0;
                }
                $statistics_data = array_values($statistics_data);
            }
            if ($requestData['statistics_type']  === 'year') {
                $stastics_own_subs = DB::select(DB::raw('select MONTH(created_at) AS month, COUNT(id) as own_subs from `user_rewards` where reward_id = 1  and reward_status = "SUCCESS" and description = "' . $requestData['streamer_name'] . '" AND created_at >= "' . $requestData['from_date'] . '" AND created_at <= "' . $requestData['to_date'] . '" AND user_id IN (select user_id from referrals_data WHERE referral_id = "' . $requestData['streamer_name'] . '") GROUP BY MONTH(created_at) ASC'));
                $stastics_other_subs = DB::select(DB::raw('select MONTH(created_at) AS month, COUNT(id) as other_subs from `user_rewards` where reward_id = 1  and reward_status = "SUCCESS" and description != "' . $requestData['streamer_name'] . '" AND created_at >= "' . $requestData['from_date'] . '" AND created_at <= "' . $requestData['to_date'] . '" AND user_id IN (select user_id from referrals_data WHERE referral_id = "' . $requestData['streamer_name'] . '") GROUP BY MONTH(created_at) ASC'));
                $stastics_other_rewards = DB::select(DB::raw('select MONTH(created_at) AS month, COUNT(id) as other_rewards from `user_rewards` where reward_id != 1  and reward_status = "SUCCESS" AND created_at >= "' . $requestData['from_date'] . '" AND created_at <= "' . $requestData['to_date'] . '" AND user_id IN (select user_id from referrals_data WHERE referral_id = "' . $requestData['streamer_name'] . '") GROUP BY MONTH(created_at) ASC'));
                $statistics = array_merge($stastics_own_subs, $stastics_other_subs, $stastics_other_rewards);
                $months = array_unique(array_column($statistics, 'month'));
                $statistics_data = [];
                foreach ($statistics as $key => $values) {
                    $values = (array)$values;
                    if (!array_key_exists($values['month'], $statistics_data)) {
                        $statistics_data[$values['month']] = array('month' => $values['month'], 'own_subs' => 0, 'other_subs' => 0, 'other_rewards' => 0);
                    }
                    $statistics_data[$values['month']]['own_subs'] += isset($values['own_subs']) ? $values['own_subs'] : 0;
                    $statistics_data[$values['month']]['other_subs'] += isset($values['other_subs']) ? $values['other_subs'] : 0;
                    $statistics_data[$values['month']]['other_rewards'] += isset($values['other_rewards']) ? $values['other_rewards'] : 0;
                }
                $statistics_data = array_values($statistics_data);
            }
            if ($requestData['statistics_type']  === 'month') {
                $stastics_own_subs = DB::select(DB::raw('SELECT CONCAT("Week", FLOOR(((DAY(created_at) -1)/7)+1))  `month_week`, COUNT(id) AS own_subs FROM `user_rewards` WHERE reward_id = 1 AND reward_status = "SUCCESS" AND description = "' . $requestData['streamer_name'] . '" AND created_at >= "' . $requestData['from_date'] . '" AND created_at <= "' . $requestData['to_date'] . '" AND user_id IN(SELECT user_id FROM referrals_data WHERE referral_id = "' . $requestData['streamer_name'] . '") GROUP BY month_week ORDER BY MONTH(created_at),`month_week`'));

                $stastics_other_subs = DB::select(DB::raw('SELECT CONCAT("Week", FLOOR(((DAY(created_at) -1)/7)+1))  `month_week`, COUNT(id) AS other_subs FROM `user_rewards` WHERE reward_id = 1 AND reward_status = "SUCCESS" AND description != "' . $requestData['streamer_name'] . '" AND created_at >= "' . $requestData['from_date'] . '" AND created_at <= "' . $requestData['to_date'] . '" AND user_id IN (select user_id from referrals_data WHERE referral_id = "' . $requestData['streamer_name'] . '") GROUP BY month_week ORDER BY MONTH(created_at),`month_week`'));
                $stastics_other_rewards = DB::select(DB::raw('SELECT CONCAT("Week", FLOOR(((DAY(created_at) -1)/7)+1))  `month_week`, COUNT(id) AS other_rewards FROM `user_rewards` WHERE reward_id != 1 AND reward_status = "SUCCESS" AND description = "' . $requestData['streamer_name'] . '" AND created_at >= "' . $requestData['from_date'] . '" AND created_at <= "' . $requestData['to_date'] . '" AND user_id IN (select user_id from referrals_data WHERE referral_id = "' . $requestData['streamer_name'] . '") GROUP BY month_week ORDER BY MONTH(created_at),`month_week`'));

                $statistics = array_merge($stastics_own_subs, $stastics_other_subs, $stastics_other_rewards);
                $months = array_unique(array_column($statistics, 'month_week'));
                $statistics_data = [];
                foreach ($statistics as $key => $values) {
                    $values = (array)$values;
                    if (!array_key_exists($values['month_week'], $statistics_data)) {
                        $statistics_data[$values['month_week']] = array('month_week' => $values['month_week'], 'own_subs' => 0, 'other_subs' => 0, 'other_rewards' => 0);
                    }
                    $statistics_data[$values['month_week']]['own_subs'] += isset($values['own_subs']) ? $values['own_subs'] : 0;
                    $statistics_data[$values['month_week']]['other_subs'] += isset($values['other_subs']) ? $values['other_subs'] : 0;
                    $statistics_data[$values['month_week']]['other_rewards'] += isset($values['other_rewards']) ? $values['other_rewards'] : 0;
                }
                $statistics_data = array_values($statistics_data);
            }

            $all_own_subs = DB::select(DB::raw('select COUNT(id) as own_subs from `user_rewards` where reward_id = 1  and reward_status = "SUCCESS" and description = "' . $requestData['streamer_name'] . '" AND user_id IN (select user_id from referrals_data WHERE referral_id = "' . $requestData['streamer_name'] . '")'));
            $all_other_subs = DB::select(DB::raw('select COUNT(id) as other_subs from `user_rewards` where reward_id = 1  and reward_status = "SUCCESS" and description != "' . $requestData['streamer_name'] . '" AND user_id IN (select user_id from referrals_data WHERE referral_id = "' . $requestData['streamer_name'] . '")'));
            $all_other_rewards = DB::select(DB::raw('select COUNT(id) as other_rewards from `user_rewards` where reward_id != 1  and reward_status = "SUCCESS" AND user_id IN (select user_id from referrals_data WHERE referral_id = "' . $requestData['streamer_name'] . '")'));

            $own_subs = isset($all_own_subs[0]) ? $all_own_subs[0]->own_subs : 0;
            $other_subs = isset($all_other_subs[0]) ? $all_other_subs[0]->other_subs : 0;
            $other_rewards = isset($all_other_rewards[0]) ? $all_other_rewards[0]->other_rewards : 0;
            $overall_progress_data = array(
                array("name" => 'Own subscriptions', "value" => $own_subs),
                array("name" => 'Other subscriptions', "value" => $other_subs),
                array("name" => 'Other rewards', "value" => $other_rewards)
            );

            $total_data = $own_subs + $other_subs + $other_rewards;

            $own_subs_percentage = (!empty($total_data)) ? round(($own_subs / $total_data) * 100, 2) : 0;
            $other_subs_percentage = (!empty($total_data)) ? round(($other_subs / $total_data) * 100, 2) : 0;
            $other_rewards_percentage = (!empty($total_data)) ? round(($other_rewards / $total_data) * 100, 2) : 0;

            $streamers_progress_in_percentage = array(
                'own_subs' => $own_subs_percentage,
                'other_subs' => $other_subs_percentage,
                'other_rewards' => $other_rewards_percentage
            );

            if (!empty($streamers_progress_in_percentage)) {
                $data['streamers_progress_in_percentage'] = $streamers_progress_in_percentage;
            }

            if (!empty($overall_progress_data)) {
                $data['overall_progress_data'] = $overall_progress_data;
            }

            if (!empty($statistics_data)) {
                $data['statistics_data'] = $statistics_data;
            }
            if (!empty($data) && !empty($total_data)) {
                return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language), 200);
            } else {
                return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), json_decode("{}"), 201);
            }
        } else {
            return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), null, 400);
        }
    }
}
