<?php

namespace App\Http\Controllers\API\V1;

use App\BrandWallet;
use App\Campaign;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Exports\CampaignReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Lang;
use App\Log;
use App\Rewards;
use App\UserCoins;
use App\UserCoinsBalances;
use App\UserRewards;

class AdminDashboardController extends Controller
{
    /**
     * @GetDashboardCounts - This API is used for get dashboard counts & funds related data.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetDashboardCounts(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetDashboardCounts";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }
        try {
            $admin_id = Auth::id();
            $user_id = User::where('user_type', 3)->where('id', $admin_id)->first();
            if (!empty($user_id)) {
                $result = DB::select('call getDashboardCounts()');
                $response = array(
                    "totalUsers" => 0,
                    "totalBrands" => 0,
                    "totalFund" => 0,
                    "totalSpent" => 0,
                    "totalProfit" => 0
                );
                if (is_array($result) && count($result) > 0) {
                    $result = $result[0];
                    $response["totalUsers"] = $result->TotalUsers;
                    $response["totalBrands"] = $result->TotalBrands;
                    $response["totalFund"] = $result->TotalFunds;
                    $response["totalSpent"] = number_format($result->TotalRedeems, 2);
                    $response["totalProfit"] = number_format($result->Profits, 2);
                }
                return $this->sendResponse($response, Lang::get('common.success', array(), $this->selected_language));
            } else {
                return $this->sendError(Lang::get('auth.unauthorized_user', array(), $this->selected_language), null, 401);
            }
        } catch (Exception $e) {
            return $this->sendError(Lang::get('common.something_went_wrong', array(), $this->selected_language), null, 500);
        }
    }

    /**
     * @GetBrandRegisteredInYear - This API is used for get brands by YEAR.
     * 
     * @param {Number} $year
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetBrandRegisteredInYear($year)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetBrandRegisteredInYear";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }
        try {
            $admin_id = Auth::id();
            $user_id = User::where('user_type', 3)->where('id', $admin_id)->first();
            if (!empty($user_id)) {
                $year = (!empty($year)) ? $year : date('Y');
                $result = DB::select('call getRegisteredBrandInYear(?)', array($year));
                return $this->sendResponse($result, Lang::get('common.success', array(), $this->selected_language));
            } else {
                return $this->sendError(Lang::get('auth.unauthorized_user', array(), $this->selected_language), null, 401);
            }
        } catch (Exception $e) {
            return $this->sendError(Lang::get('common.something_went_wrong', array(), $this->selected_language), null, 500);
        }
    }

    /**
     * @GetHighestPaidCampaign - This API is used for get list of highest paid campaign.
     * 
     * @param \Illuminate\Http\Request $request
     * @param $request Request
     * @param $from  From date
     * @param $to  To date
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetHighestPaidCampaign(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetHighestPaidCampaign";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }
        try {
            $admin_id = Auth::id();
            $user_id = User::where('user_type', 3)->where('id', $admin_id)->first();
            if (!empty($user_id)) {
                $from = $request->json("from");
                $to = $request->json("to");
                if (empty($from) || empty($to)) {
                    return $this->sendError(Lang::get('common.missing_require_data', array(), $this->selected_language), json_decode("{}"), 400);
                }
                $from = date("Y-m-d", strtotime($from));
                $to = date("Y-m-d", strtotime($to));
                $result = DB::select('call getTopPaidCampaigns(?,?)', array($from, $to));
                $result = (!empty($result)) ? $result : array();
                return $this->sendResponse($result, Lang::get('common.success', array(), $this->selected_language));
            } else {
                return $this->sendError(Lang::get('auth.unauthorized_user', array(), $this->selected_language), null, 401);
            }
        } catch (Exception $e) {
            return $this->sendError(Lang::get('common.something_went_wrong', array(), $this->selected_language), null, 500);
        }
    }

    /**
     * @GetCampaignReport - This API is used for get campaign report.
     * 
     * @param \Illuminate\Http\Request $request
     * @param $request Request
     * @param $from  From date
     * @param $to  To date
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetCampaignReport(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetCampaignReport";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }
        try {
            $admin_id = Auth::id();
            $user_id = User::where('user_type', 3)->where('id', $admin_id)->first();
            if (!empty($user_id)) {
                $brands = $request->json("brand");
                $type = $request->json("type");
                $campaigns = $request->json("campaign");
                $from = $request->json("from");
                $to = $request->json("to");
                $status = $request->json("status");
                $isExport = $request->json("isExport");
                $exportFile = "";

                $from = (!empty($from)) ? date("Y-m-d", strtotime($from)) : "";
                $to = (!empty($to)) ? date("Y-m-d", strtotime($to)) : "";
                $isExport = ($isExport) ? true : false;

                $result = DB::select('call getCampaignReport(?,?,?,?,?,?)', array($from, $to, $brands, $type, $campaigns, $status));
                $result = (!empty($result)) ? $result : array();
                if ($isExport && !empty($result)) {
                    $filenm = 'CampaignReport-' . date('dmY') . '.xlsx';
                    $filepath = 'reports/' . $filenm;
                    Excel::store(new CampaignReport($result), $filepath, 'public_dir');
                    $diskPath = Storage::disk('public_dir');
                    if ($diskPath->exists($filepath)) {
                        $exportFile = $diskPath->url($filepath);
                    }
                }
                return $this->sendResponse(array("records" => $result, "file" => $exportFile), Lang::get('common.success', array(), $this->selected_language));
            } else {
                return $this->sendError(Lang::get('auth.unauthorized_user', array(), $this->selected_language), null, 401);
            }
        } catch (Exception $e) {
            return $this->sendError(Lang::get('common.something_went_wrong', array(), $this->selected_language), null, 500);
        }
    }

    public function getDashboardKpis(Request $request)
    {
        $requestData = $request->json()->all();

        $start_date = $request->has('start_date') && !empty($requestData['start_date']) ? date('Y-m-d', strtotime($requestData['start_date'])) : date('Y-m-01 00:00:00');
        $end_date = $request->has('end_date') && !empty($requestData['end_date']) ? date('Y-m-d', strtotime($requestData['end_date'])) : date('Y-m-t 23:59:59');

        // count logged users in the last hour (1)
        $count_log_users = Log::where('action', 'login')->whereRaw(DB::raw('created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)'))->distinct('user_id')->count();

        // count active users logged in the last 30 days (2)
        $thirty_days_ago = date('Y-m-d', strtotime("-30 days"));
        $active_users_count = Log::where('action', 'login')->whereDate('created_at', ">=", $thirty_days_ago)->distinct('user_id')->count();

        // get count of active campaigns (3)
        $count_current_campaigns = Campaign::where('start_date', '<=', date('Y-m-d'))
            ->where('end_date', '>=', date('Y-m-d'))
            ->where('active', 1)->count();

        // Active tasks (3.5)
        $count_active_tasks = Campaign::where('start_date', '>=', $start_date)->where('end_date', '<=', $end_date)->where('active', '1')->sum('user_target');

        // get all users unspent coins (4)
        $total_users_unspend_coins = UserCoinsBalances::sum('coin_balance');
        $total_users_unspend_coins_euros = $total_users_unspend_coins * 0.006;

        // USERS - get registered users for the selected period (5)
        $users_registered_count = User::whereBetween('created_at', [$start_date, $end_date])->where('active', 1)->count();

        // USERS - retention (6)
        //  (active users at end of period - registered users in period)/active users at start of period  
        //  (Users end period - users registered in the period)/users at the beginning.

        $startPeriodUsers =  Log::where('action', 'login')->whereRaw(DB::raw('created_at > DATE_SUB("' . $end_date . '", INTERVAL 1 YEAR)'))->where('created_at', '<=', $end_date)->distinct('user_id')->count();

        $endPeriodUsers = Log::where('action', 'login')->whereRaw(DB::raw('created_at > DATE_SUB("' . $start_date . '", INTERVAL 1 YEAR)'))->where('created_at', '<=', $start_date)->distinct('user_id')->count();
        $retentionUsers = ($startPeriodUsers > 0) ? (($endPeriodUsers - $users_registered_count) * 100 / $startPeriodUsers) : 0;
        $retentionUsers = ($retentionUsers) > 0 ? $retentionUsers : 0;

        // USERS - sessions/user - Num of logs from active users / num of active users (7)
        // - Average number of active user logins.

        $sessionsPerUser =  DB::select("SELECT AVG(sessions_per_user) as `sessions_per_user` from (select user_id, count(id) as sessions_per_user from log where action = 'login' and created_at BETWEEN '$start_date' AND '$end_date' group by user_id) a");

        $sessionsPerUser = $sessionsPerUser[0]->sessions_per_user;

        // USERS - LTV (days) - AVERAGE(last connection - sign up date) (8)
        // - Average number of days users spend on the app.
        $ltv_data = DB::select("select avg(ltv_per_user) ltv from (
            select user_id, max(created_at) last_log, DATEDIFF(max(created_at),min(created_at)) ltv_per_user from log
            group by user_id) user_ltv
            where user_ltv.last_log > DATE_SUB('$end_date', INTERVAL 1 YEAR)");


        $ltv_days = (isset($ltv_data[0])) ? (int) $ltv_data[0]->ltv : 0;
        // CAMPAIGNS - Num of created (and approved) campaigns in that period (selected from the date picker at the top) (9)
        $campaigns_approved = Campaign::whereBetween('created_at', [$start_date, $end_date])->where('is_approved', 'APPROVED')->count();

        // CAMPAIGNS - Task reward / (Campaign funds / 2) (10)
        // - Sum of the number of tasks of each of the campaigns created in the period.
        // select sum(user_target) FROM campaigns WHERE is_approved = 'APPROVED' AND created_at BETWEEN '2022-03-01' AND '2022-03-31'
        $tasks = Campaign::whereBetween('created_at', [$start_date, $end_date])->where('is_approved', 'APPROVED')->sum('user_target');

        // CAMPAIGNS - estimated revenue - Sum of all campaigns funds (11)
        $sum_estimated_revenue = Campaign::whereBetween('created_at', [$start_date, $end_date])->where('is_approved', 'APPROVED')->sum('sub_total');
        $estimated_revenue = number_format($sum_estimated_revenue, 3, '.', ',');

        // CAMPAIGNS - Campaign unspent funds (12)
        // $total_funds = Campaign::where('is_approved', 'APPROVED')->sum('sub_total');
        // $total_redeemed_funds = UserCoins::where('reward_id', '!=', null)->sum('debit');
        // $final_campaign_unspent_funds = $total_funds - ($total_redeemed_funds / 50);
        // $campaign_unspent_funds =  number_format($final_campaign_unspent_funds, 3, '.', ',');

        // SELECT SUM(credit) - SUM(debit) FROM `brand_wallets` bw INNER JOIN campaigns cm ON cm.id = bw.campaign_id;

        // $campaign_unspent_funds = DB::select("SELECT SUM(credit) - SUM(debit) as unspentFunds FROM `brand_wallets` bw INNER JOIN campaigns cm ON cm.id = bw.campaign_id WHERE cm.created_at BETWEEN $start_date AND $end_date
        // ");

        $campaign_unspent_funds = BrandWallet::select(DB::raw('SUM(credit) - SUM(debit) as unspentFunds'))
            ->whereHas('campaign', function ($q) use ($start_date, $end_date) {
                $q->whereBetween('created_at', [$start_date, $end_date]);
            })->first();

        $campaign_unspent_funds = number_format($campaign_unspent_funds->unspentFunds, 3, '.', ',');

        // REWARDS - Number of rewards delivered in the period (13)
        $rewards_count_in_period  = UserRewards::whereBetween('created_at', [$start_date, $end_date])->count();

        // REWARDS - conversion rate - Users with rewards*100 / users (14)
        // - Percentage of users who have converted compared to registered users.
        // $user_with_rewards = User::where('user_type', 2)->where('active', 1)
        //     ->whereHas('user_rewards_data', function ($q) {
        //         $q->where('reward_status', 'SUCCESS');
        //     })->count();

        $user_with_rewards = UserRewards::whereBetween('created_at', [$start_date, $end_date])->where('reward_status', 'SUCCESS')->distinct('user_id')->count();

        $total_users = User::where('user_type', 2)->where('active', 1)->count();
        $conversion_rate = ($total_users > 0) ? ($user_with_rewards * 100) / $total_users : 0;
        $final_conversion_rate = number_format($conversion_rate, 2, '.', '');

        // REWARDS - rewards cost - num of rewards * real cost in euros (15)
        $total_user_rewards_coins = UserRewards::whereBetween('created_at', [$start_date, $end_date])->where('reward_status', 'SUCCESS')->sum('real_cost');
        // $rewards_cost_val = $total_user_rewards_coins * 0.006;
        $rewards_cost = number_format($total_user_rewards_coins, 3, '.', '');

        // REWARDS - rewards/user - num of rewards / num users with at least one reward (16)
        // - Rewards delivered between number of users with at least one reward.
        // $total_user_rewards = UserRewards::whereBetween('created_at', [$start_date, $end_date])->where('reward_status', 'SUCCESS')->count('id');
        $total_user_rewards = DB::select("SELECT COUNT(id) as total_user_rewards FROM user_rewards WHERE `reward_status` = 'SUCCESS' AND created_at BETWEEN '$start_date' AND '$end_date'");
        $user_rewards = UserRewards::select('user_id')->whereBetween('created_at', [$start_date, $end_date])->where('reward_status', 'SUCCESS')->groupBy('user_id')->get();
        $user_rewards_count = count($user_rewards);
        $total_user_rewards = $total_user_rewards[0]->total_user_rewards;
        $rewards_per_users = $total_user_rewards . "," . $user_rewards_count;

        //  COINS - rewarded coins - Sum of rewarded coins in that period (17)
        // - Total amount of coins delivered in the period
        $total_rewards_coins_in_period = UserCoins::whereBetween('created_at', [$start_date, $end_date])->sum('credit');
        $rewards_coins_in_period = number_format($total_rewards_coins_in_period, 3, '.', '');

        //  COINS - rewarded euros - rewarded coins * 0,0060 in that period (18)
        // - Total amount of euros delivered in the period
        $rewards_cost_in_period_val = $total_rewards_coins_in_period * 0.006;
        $rewards_cost_in_period_euro = number_format($rewards_cost_in_period_val, 3, '.', '');

        //  COINS - spent euros - rewarded coins * 0,0060 in that period (19)
        // - Total amount redeemed for prizes in euros.
        $total_redeemed_coins_in_period = UserRewards::where('reward_status', 'SUCCESS')->whereBetween('created_at', [$start_date, $end_date])->sum('redeem_coins');
        $redeemed_rewards_cost_in_period_val = $total_redeemed_coins_in_period * 0.006;
        $redeemed_rewards_cost_in_period_euro = number_format($redeemed_rewards_cost_in_period_val, 3, '.', '');

        //  COINS - spent euros/user - spent euros / num users with at least one reward (20)
        // - Amount redeemed divided by number of users with at least one reward.
        // $total_redeemed_coins = UserRewards::where('reward_status', 'SUCCESS')->sum('redeem_coins');
        // $redeemed_rewards_cost_val = $total_redeemed_coins * 0.006;
        // $user_with_rewards = User::where('user_type', 2)->where('active', 1)
        //     ->whereHas('user_rewards_data', function ($q) {
        //         $q->where('reward_status', 'SUCCESS');
        //     })->count();

        // $spent_euros_per_user_val = ($user_with_rewards > 0) ? $redeemed_rewards_cost_val / $user_with_rewards : 0;
        // $spent_euros_per_user = number_format($spent_euros_per_user_val, 3, '.', '');

        // $sessionsPerUser =  DB::select("SELECT AVG(sessions_per_user) as `sessions_per_user` from (select user_id, count(id) as sessions_per_user from log where action = 'login' and created_at BETWEEN '2022-03-01' AND '2022-03-31' group by user_id) a");

        $redeemed_coins_per_user = DB::select("SELECT AVG(redeemed_coins_per_user) as redeemed_coins_per_user from (select user_id, SUM(redeem_coins) as redeemed_coins_per_user from user_rewards WHERE reward_status = 'SUCCESS' AND created_at BETWEEN '$start_date' AND '$end_date' group by user_id) a");

        $redeemed_coins_per_user = $redeemed_coins_per_user[0]->redeemed_coins_per_user;
        $redeemed_coins_per_user = number_format($redeemed_coins_per_user, 3, '.', '');
        $spent_euros_per_user = $redeemed_coins_per_user * 0.006;
        $spent_euros_per_user = number_format($spent_euros_per_user,3, '.','');

        $dashboard_metrices = array(
            'logged_users' => $count_log_users, //(1)
            'active_users' => $active_users_count, //(2)
            'count_current_campaigns' => $count_current_campaigns, //(3)
            'count_active_tasks' => $count_active_tasks,
            'total_users_unspend_coins' => $total_users_unspend_coins, //(4)
            'total_users_unspend_coins_euros' => round($total_users_unspend_coins_euros, 3), //(4)
            'count_users_registered' => $users_registered_count, // (5)
            'retention_users' => round($retentionUsers, 0) . '%', // (6)
            'sessions_per_user' => $sessionsPerUser ? $sessionsPerUser : 0, // (7)
            'ltv_days' => $ltv_days, // (8)
            'campaigns_approved' => $campaigns_approved, // (9)
            'tasks' => $tasks, // (10)
            'estimated_revenue' => $estimated_revenue . ' €', // (11)
            'campaign_unspent_funds' => $campaign_unspent_funds . ' €',  // (12)
            'rewards_count_in_period' => $rewards_count_in_period,  // (13)
            'final_conversion_rate' => $final_conversion_rate, // (14)
            'rewards_cost' => $rewards_cost . ' €', // (15)
            'rewards_per_users' => $rewards_per_users, // (16)
            'rewards_coins_in_period' => $rewards_coins_in_period, // (17)
            'rewards_cost_in_period_euro' => $rewards_cost_in_period_euro . ' €', // (18)
            'redeemed_rewards_cost_in_period_euro' => $redeemed_rewards_cost_in_period_euro . ' €', // (19) 
            'spent_euros_per_user' => $spent_euros_per_user . ' €' // (20)
        );
        return $this->sendResponse($dashboard_metrices, Lang::get('common.success', array(), $this->selected_language));
    }
}
