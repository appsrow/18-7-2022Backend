<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\State;
use App\Rewards;
use App\Country;
use App\UserCoins;
use App\User;
use App\Helpers\GeneralHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use App\Log;
use Exception;
use Illuminate\Support\Facades\Validator;

class OtherController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Other Controller
    |--------------------------------------------------------------------------
    |
    | This controller will return datas such as countries, states & rewards
    |
    */
    /**
     * @load_country - This API is used for get list of countries.
     * 
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function load_country()
    {
        $country = Country::all();
        if (!empty($country)) {
            return $this->sendResponse($country, Lang::get("common.success", array(), $this->selected_language), 200);
        } else {
            return $this->sendError(Lang::get("common.country_not_found", array(), $this->selected_language), null, 201);
        }
    }

    /**
     * @load_state_by_country - This API is used for get list of states by country ID.
     * 
     * @param  {Number} $id
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function load_state_by_country(Request $request, $id)
    {
        if (!empty($id)) {
            $states_name = State::where('country_id', $id)->orderBy('state_name')->get();
            $totalStates = count($states_name);
            if ($totalStates > 0) {
                return $this->sendResponse($states_name, Lang::get("common.success", array(), $this->selected_language), 200);
            } else {
                return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), null, 201);
            }
        } else {
            return $this->sendError(Lang::get("common.country_id_missing", array(), $this->selected_language), null, 400);
        }
    }

    /**
     * @GetAllRewards - This API is used for get list of rewards.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetAllRewards()
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetAllRewards";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }
        $user_id = Auth::id();
        $loginUser = User::where('user_type', 2)->where('id', $user_id)->first();
        if (!empty($loginUser)) {
            $closing_amount = UserCoins::where('user_id', $user_id)->latest('id')->first();
            if (!empty($closing_amount)) {
                $temp_wallet_price = $closing_amount->closing_balance;
                $rewards = Rewards::where('active', 1)->get();
                $total_rewards = count($rewards);
                if ($total_rewards > 0) {
                    $data = [];
                    foreach ($rewards as $reward) {
                        //check image
                        $filepath = public_path('rewards') . "/" . $reward->photo;
                        if (!empty($reward->photo) && file_exists($filepath)) {
                            $reward->photo = url('rewards') . "/" . $reward->photo;
                        } else {
                            $reward->photo = "";
                        }

                        //check progress of reward for the user
                        $mimimum = $reward->minimum_coins;
                        $percentage = ($temp_wallet_price * 100) / $mimimum;
                        if ($percentage >= 100) {
                            $percentage = 100;
                        }
                        $reward->percentage = $percentage;

                        //Check for paypal funds
                        // if ($reward->id == '2') {
                        //     $paypal_auth_uri = env("PAYPAL_AUTH_URL", null);
                        //     $paypal_clientId = env("CLIENT_ID", null);
                        //     $paypal_secret = env("SECRET", null);
                        //     $paypal_get_balance = env("PAYPAL_BALANCE", null);

                        //     if (!empty($paypal_auth_uri) && !empty($paypal_clientId) && !empty($paypal_secret) && !empty($paypal_get_balance)) {
                        //         try {
                        //             $paypal_auth_data = array(
                        //                 'headers' =>
                        //                 [
                        //                     'Accept' => 'application/json',
                        //                     'Accept-Language' => 'en_US',
                        //                     'Content-Type' => 'application/x-www-form-urlencoded',
                        //                 ],
                        //                 'body' => 'grant_type=client_credentials',
                        //                 'auth' => [$paypal_clientId, $paypal_secret, 'basic']
                        //             );

                        //             $paypal_auth_response_data = GeneralHelper::RequestCurl('POST', $paypal_auth_uri, $paypal_auth_data);
                        //             if (!$paypal_auth_response_data['status']) {
                        //                 return $this->sendError(Lang::get("campaign.paypal_connection_failed", array(), $this->selected_language), null, 201);
                        //             }

                        //             //check for token
                        //             $paypal_auth_status = isset($paypal_auth_response_data['status']) ? $paypal_auth_response_data['status'] : "";
                        //             $paypal_auth_token = isset($paypal_auth_response_data['data']['access_token']) ? $paypal_auth_response_data['data']['access_token'] : "";
                        //             if (!empty($paypal_auth_token) && !empty($paypal_auth_token)) {

                        //                 //check for balance
                        //                 $paypal_balance_data = array(
                        //                     'headers' =>
                        //                     [
                        //                         'Content-Type' => 'application/json',
                        //                         'Authorization' => "Bearer $paypal_auth_token",
                        //                     ]
                        //                 );
                        //                 //let's do payout request
                        //                 $client = new \GuzzleHttp\Client();
                        //                 $balance_response = $client->request('GET', $paypal_get_balance, $paypal_balance_data);
                        //                 $balance_body = json_decode($balance_response->getBody(), true);
                        //                 $balance_status_code = $balance_response->getStatusCode();
                        //                 $balances = isset($balance_body['balances']) ? $balance_body['balances'] : "";

                        //                 //check response & status code
                        //                 if (!empty($balance_body) && !empty($balance_status_code && !empty($balances))) {
                        //                     foreach ($balances as $balance) {
                        //                         $primary = isset($balance['primary']) ? $balance['primary'] : "";
                        //                         if (!empty($primary)) {
                        //                             //echo ($balance['available_balance']['value']);
                        //                             $balance_value = isset($balance['available_balance']['value']) ? $balance['available_balance']['value'] : 0;
                        //                             //Check if balance is lower that a Paypal reward (10 €) -> Not enough coins
                        //                             $paypal_balance_limit =  env("PAYPAL_BALANCE_LIMIT", 10);
                        //                             if ($balance_value < $paypal_balance_limit) {
                        //                                 $reward->active = 0;
                        //                             }
                        //                         }
                        //                     }
                        //                 }
                        //             }
                        //         } catch (Exception  $e) {
                        //             return $this->sendError($e->getMessage(), null, 500);
                        //         }
                        //     }
                        // }
                    }
                    $data['rewards'] = $rewards;
                    return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language), 200);
                } else {
                    return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), null, 201);
                }
            } else {
                return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), 201);
            }
        } else {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), null, 403);
        }
    }
}
