<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\User;
use App\Company;
use App\State;
use App\UserCoins;
use App\Country;
use App\UserCoinsBalances;
use App\Campaign;
use Illuminate\Support\Facades\Auth;
use App\Helpers\GeneralHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\BrandWallet;
use App\BrandWalletBalance;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Lang;
use App\PromotedStreamer;
use App\Rewards;
use Exception;
use Illuminate\Support\Facades\DB;
use App\UserRewards;
use App\Log;
use App\ReferralStreamersName;
use App\ReferralData;

class TwitchController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Twitch Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles all twitch related functionalites
    */
    protected $twitch_token;

    function __construct(Request $request)
    {
        parent::__construct($request);
        $this->GetTwitchToken();
    }

    /**
     * @GetTwitchToken - This API is used for authorise to twitch. 
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function GetTwitchToken()
    {

        if (empty($this->twitch_token)) {
            try {

                /* Get Twitch Token */
                $twitch_auth_uri = env("TWITCH_AUTH_URL", null);
                $twitch_clientId = env("TWITCH_CLIENT_ID", null);
                $twitch_secret = env("TWITCH_CLIENT_SECRET", null);
                if (empty($twitch_auth_uri) || empty($twitch_clientId) || empty($twitch_secret)) {
                    return false;
                }

                //profile uri
                $twitch_auth_uri = str_replace(array("{{TWITCH_CLIENT_ID}}", "{{TWITCH_CLIENT_SECRET}}"), array($twitch_clientId, $twitch_secret), $twitch_auth_uri);

                $get_twitch_data = GeneralHelper::RequestCurl('POST', $twitch_auth_uri, array());
                if (!$get_twitch_data['status']) {
                    return false;
                }
                $this->twitch_token = $get_twitch_data['data']['access_token']; //bearer token

            } catch (Exception $e) {
                return false;
            }
        }
    }

    /**
     * @GetTwitchUser - This API is used for get user profile of twitch. 
     * @param mixed $twitch_id
     * @param string $by
     * @return array $return
     */
    private function GetTwitchProfile($twitch_id, $by_value = "id")
    {
        $return = array();
        try {

            $twitch_auth_uri = env("TWITCH_API_URL", null);
            $twitch_client_id = env("TWITCH_CLIENT_ID", null);

            if (empty($twitch_id) || empty($twitch_auth_uri) || empty($twitch_client_id)) {
                return $return;
            }

            //check if the twitch_id is array
            $twitch_ids = "";
            if (is_array($twitch_id) && count($twitch_id) > 0) {
                //let create query string of multiple ids
                foreach ($twitch_id as $tw_id) {
                    $twitch_ids .= (!empty($twitch_ids)) ? "&" . $by_value . "=" . $tw_id  : "?" . $by_value . "=" . $tw_id;
                }
            } else {
                $twitch_ids = "?" . $by_value . "=" . $twitch_id;
            }

            //profile uri
            $twitch_auth_uri .= "/helix/users" . $twitch_ids;
            $options = array(
                "headers" => array(
                    "Client-id" => $twitch_client_id,
                    "Authorization" => "Bearer " . $this->twitch_token
                )
            );

            $get_twitch_data = GeneralHelper::RequestCurl('GET', $twitch_auth_uri, $options);
            if (!empty($get_twitch_data) && $get_twitch_data['status']) {
                $mix_profile_data = $get_twitch_data['data']['data']; //return array list

                if (count($mix_profile_data) > 0) {
                    foreach ($mix_profile_data as $profile_data) {
                        $return[] = array(
                            "id" => $profile_data['id'],
                            "login" => $profile_data['login'],
                            "display_name" => $profile_data['display_name'],
                            "profile_image_url" => $profile_data['profile_image_url'],
                            "view_count" => $profile_data['view_count']
                        );
                    }
                }
            }
        } catch (Exception $e) {
            dd($e);
            $return = array();
            // print_r($e->getMessage());
        }

        return $return;
    }


    /**
     * @GetPromotedStreamers - This API is used for get list of promoted streamers. 
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetPromotedStreamers(Request $request)
    {
        try {
            //check authenticate user exist
            $user_id = Auth::id();
            $user_data = User::where('user_type', 2)->where('id', $user_id)->first();
            if (empty($user_data)) {
                return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 403);
            }

            if (!$this->twitch_token) {
                return $this->sendError(Lang::get("common.unauthorized", array(), $this->selected_language), json_decode("{}"), 403);
            }

            $requestData = $request->json()->all();
            $page = (isset($requestData['page'])) ? $requestData['page'] : 1;
            // Check if login user has referral streamer attached
            $checkReferralExist = ReferralData::where('user_id', $user_id)->first();
            if (!empty($checkReferralExist) && $page === 1) {
                $refPromotedStreamerId = $checkReferralExist->referral_id;
                $refPromotedStreamer = PromotedStreamer::where('twitch_id', $checkReferralExist->referral_id)->first();
            }
            $limit = (isset($requestData['limit'])) ? $requestData['limit'] : 10;
            if (isset($refPromotedStreamerId) && $page === 1) {
                $limit = (isset($requestData['limit'])) ? $requestData['limit'] - 1 : 9;
            }
            $offset = ($page - 1) * $limit;

            $totalRecords = PromotedStreamer::all();
            $totalRecords = $totalRecords->count();

            if ($totalRecords > 0) {
                // Fetch records
                $records = new PromotedStreamer;
                $records = $records->select('*');
                if (isset($refPromotedStreamerId)) {
                    $records = $records->where('twitch_id', '!=', $refPromotedStreamerId);
                }
                $records = $records->orderBy('price', 'ASC')->orderBy('id', 'ASC')->offset($offset)->limit($limit)->get();

                $promoted_list = array();
                if (isset($refPromotedStreamerId) && $page === 1) {
                    $promoted_list[$refPromotedStreamerId] = !empty($refPromotedStreamer) ? $refPromotedStreamer->price : env('TWITCH_STANDARD_STREAMER_COINS');
                }
                //set array of key value
                foreach ($records as $key => $row) {
                    $promoted_list[$row->twitch_id] = $row->price;
                }

                //get all profiles data
                $streamers_list = $this->GetTwitchProfile(array_keys($promoted_list), "login");
                $refPromotedStreamerFirst = [];
                //set array of key value
                foreach ($streamers_list as $key => $row) {
                    $count_referral = ReferralData::where('user_id', $user_id)->where('referral_id', $row['login'])->count();
                    $streamers_list[$key]['coins'] = (isset($promoted_list[$row['login']])) ? $promoted_list[$row['login']] : env("TWITCH_STANDARD_STREAMER_COINS", 799);
                    $streamers_list[$key]['standard_coins'] = env("TWITCH_STANDARD_STREAMER_COINS", 799);
                    if ($count_referral > 0) {
                        $streamers_list[$key]['discounted_coins'] = env("TWITCH_REFERRAL_STREAMER_COINS", 500);
                        if ($page === 1) {
                            $refPromotedStreamerFirst[] =  $streamers_list[$key];
                            unset($streamers_list[$key]);
                        }
                    } else {
                        $streamers_list[$key]['discounted_coins'] = 0;
                    }
                }
                $finalStreamers = array_merge($refPromotedStreamerFirst, $streamers_list);

                $response = array("totalRecords" => $totalRecords, "totalPages" => ceil($totalRecords / $limit), "limit" => $limit, "page" => $page, "records" => $finalStreamers);

                return $this->sendResponse($response, Lang::get("common.success", array(), $this->selected_language), 200);
            } else {
                return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), null, 201);
            }
        } catch (Exception $e) {
            return $this->sendError(Lang::get("common.something_went_wrong", array(), $this->selected_language), null, 500);
        }
        return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), null, 201);
    }

    /**
     * @GetUserTwitchProfile - This API is used to get user's twitch profile. 
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetUserTwitchProfile(Request $request, $twitch_id)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "GetUserTwitchProfile";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }
        try {
            //check authenticate user exist
            $user_id = Auth::id();
            $user_data = User::where('user_type', 2)->where('id', $user_id)->first();
            if (empty($user_data)) {
                return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 403);
            }

            if (empty($twitch_id)) {
                return $this->sendError(Lang::get("common.request_empty", array(), $this->selected_language), null, 400);
            }

            $twitch_profile = $this->GetTwitchProfile($twitch_id, "login");
            if (!empty($twitch_profile)) {
                $twitch_profile = $twitch_profile[0];
                return $this->sendResponse($twitch_profile, Lang::get("common.success", array(), $this->selected_language), 200);
            } else {
                return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), null, 201);
            }
        } catch (Exception $e) {
            // return $this->sendError($e->getMessage(), null, 500);
            return $this->sendError(Lang::get("common.something_went_wrong", array(), $this->selected_language), null, 500);
        }

        return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), null, 201);
    }

    /**
     * @SearchTwitchStreamers - This API is used for search streamers. 
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function SearchTwitchStreamers(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "SearchTwitchStreamers";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }
        try {
            //check authenticate user exist
            $user_id = Auth::id();
            $user_data = User::where('user_type', 2)->where('id', $user_id)->first();
            if (empty($user_data)) {
                return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 403);
            }

            $requestData = $request->json()->all();

            if (empty($requestData) || count($requestData) <= 0) {
                return $this->sendError(Lang::get("common.request_empty", array(), $this->selected_language), null, 400);
            }

            if (empty($requestData['search'])) {
                return $this->sendError(Lang::get("common.request_invalid", array(), $this->selected_language), null, 400);
            }

            /* Get Twitch Token */
            $twitch_auth_uri = env("TWITCH_API_URL", null);
            $twitch_client_id = env("TWITCH_CLIENT_ID", null);
            if (empty($twitch_auth_uri) || empty($twitch_client_id)) {
                return $this->sendError(Lang::get("common.unauthorized", array(), $this->selected_language), null, 500);
            }

            // get request parameters
            $search = $requestData['search'];
            $paginate_cursor = (isset($requestData['cursor'])) ? $requestData['cursor'] : '';
            $paginate_limit = (isset($requestData['limit'])) ? $requestData['limit'] : 10;

            //get data from twitch
            $twitch_auth_uri .= "/helix/search/channels";
            $options = array(
                "headers" => array(
                    "Client-id" => $twitch_client_id,
                    "Authorization" => "Bearer " . $this->twitch_token
                ),
                "query" => array(
                    "query" => $search,
                    "first" => $paginate_limit,
                    "after" => $paginate_cursor
                )
            );

            //searching from twitch
            $get_twitch_data = GeneralHelper::RequestCurl('GET', $twitch_auth_uri, $options);

            if (empty($get_twitch_data) || !$get_twitch_data['status']) {
                return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), null, 201);
            }

            if (empty($get_twitch_data['data']) || count($get_twitch_data['data']) <= 0) {
                return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), null, 201);
            }

            //twitch search result in array
            $search_data =  $get_twitch_data['data']['data'];

            //get all profile data of all search records
            $searched_streamer_list = array();
            $searched_streamer_list = $this->GetTwitchProfile(array_column($search_data, 'id'));

            //return false if no result
            if (empty($searched_streamer_list) || count($searched_streamer_list) <= 0) {
                return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), null, 201);
            }

            // get promoted records for compare and override price/coins in search result
            $promoted_streamers = PromotedStreamer::all();
            $promoted_list = array();
            if (!empty($promoted_streamers)) {
                //set array of key value
                foreach ($promoted_streamers as $key => $row) {
                    $promoted_list[$row->twitch_id] = $row->price;
                }
            }

            //set price/coin 
            foreach ($searched_streamer_list as $key => $row) {
                $count_referral = ReferralData::where('user_id', $user_id)->where('referral_id', $row['login'])->count();
                $searched_streamer_list[$key]['coins'] = (isset($promoted_list[$row['login']])) ? $promoted_list[$row['login']] : env("TWITCH_STANDARD_STREAMER_COINS", 799);
                $searched_streamer_list[$key]['standard_coins'] = env("TWITCH_STANDARD_STREAMER_COINS", 799);
                if ($count_referral > 0) {
                    $searched_streamer_list[$key]['discounted_coins'] = env("TWITCH_REFERRAL_STREAMER_COINS", 500);
                } else {
                    $searched_streamer_list[$key]['discounted_coins'] = 0;
                }
            }

            //pagination object of twitch
            $paginate_cursor = isset($get_twitch_data['data']['pagination']) ? $get_twitch_data['data']['pagination'] : "{}";

            return $this->sendResponse(array("records" => $searched_streamer_list, "pagination" => $paginate_cursor), Lang::get("common.success", array(), $this->selected_language), 200);
        } catch (Exception $e) {
            // return $this->sendError($e->getMessage(), null, 500);
            return $this->sendError(Lang::get("common.something_went_wrong", array(), $this->selected_language), null, 500);
        }

        return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), null, 201);
    }


    /**
     * @UserTwitchSubscription - This API is used for request subscripiton of twitch channel. 
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function UserTwitchSubscription(Request $request)
    {
        try {
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "UserTwitchSubscription";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        }
        //check user exist
        $user_id = Auth::id();
        $user_data = User::where('user_type', 2)->where('id', $user_id)->first();
        if (empty($user_data)) {
            return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 403);
        }

        //get all request data & validate
        $requestData = $request->json()->all();
        try {
            if (empty($requestData) || count($requestData) <= 0) {
                return $this->sendError(Lang::get("common.request_empty", array(), $this->selected_language), null, 400);
            }

            if (empty($requestData['rewards_id']) || empty($requestData['user_twitch_id']) || empty($requestData['streamer_twitch_id'])) {
                return $this->sendError(Lang::get("common.missing_require_data", array(), $this->selected_language), null, 400);
            }

            //get reward data
            $reward_data = Rewards::select('*')->where('id', $requestData['rewards_id'])->first();
            if (empty($reward_data)) {
                return $this->sendError(Lang::get("campaign.reward_not_found", array(), $this->selected_language), null, 201);
            }

            // get request parameters
            $user_twitch_id = $requestData['user_twitch_id'];
            $streamer_twitch_id = $requestData['streamer_twitch_id'];

            //check user's twitch id
            $user_twitch_profile = $this->GetTwitchProfile($user_twitch_id, "login");
            $streamer_twitch_profile = $this->GetTwitchProfile($streamer_twitch_id);

            //check empty profiles
            if (empty($user_twitch_profile)) {
                return $this->sendError(Lang::get("campaign.user_twitch_profile_not_found", array(), $this->selected_language), null, 201);
            }
            if (empty($streamer_twitch_profile)) {
                return $this->sendError(Lang::get("campaign.streamer_twitch_profile_not_found", array(), $this->selected_language), null, 201);
            }

            $user_twitch_profile = $user_twitch_profile[0];
            $streamer_twitch_profile = $streamer_twitch_profile[0];

            //check whether its promoted streamer then use price(coins) of promoted streamer
            $check_promoted_streamer = PromotedStreamer::where('twitch_id', $streamer_twitch_profile['login'])->first();
            $subscription_price = (!empty($check_promoted_streamer)) ? $check_promoted_streamer->price : env("TWITCH_STANDARD_STREAMER_COINS", 799);

            // check if the streamer is joined by referral link
            $count_referral = ReferralData::where('user_id', $user_id)->where('referral_id', $streamer_twitch_profile['login'])->count();
            if ($count_referral > 0) {
                $subscription_price = env("TWITCH_REFERRAL_STREAMER_COINS", 500);
            }

            //check user coin balance
            $user_closing_amount = UserCoinsBalances::select('coin_balance')->where('user_id', $user_id)->latest('id')->first();
            if ($user_closing_amount->coin_balance < $subscription_price) {
                return $this->sendError(Lang::get("campaign.user_not_enough_coins", array(), $this->selected_language), null, 201);
            }

            //everything is verfied let's proceed
            //add user reward and store it in user coins
            $user_reward = UserRewards::addUserReward(array(
                "user_id" => $user_id,
                "reward_id" => $reward_data->id,
                "redeem_coins" => $subscription_price,
                "description" => (isset($streamer_twitch_profile['display_name'])) ? $streamer_twitch_profile['display_name'] : $reward_data->description,
                "user_twitch_id" => $user_twitch_id,
                "streamer_twitch_id" => $streamer_twitch_id
            ));

            if (empty($user_reward)) {
                return $this->sendError(Lang::get("campaign.user_redeem_reward_failed", array(), $this->selected_language), null, 201);
            }

            //send mail notification

            $from_email =  env("MAIL_FROM_ADDRESS", null);
            $from_name =  env("MAIL_FROM_NAME", null);

            $mail_user_data = array(
                "streamer_name" => isset($streamer_twitch_profile['display_name']) ? $streamer_twitch_profile['display_name'] : "Twitch",
                "user_name"     => ucwords($user_data->first_name . " " . $user_data->last_name),
                "user_email"     => $user_data->email,
                "first_name"     => $user_data->first_name,
                "last_name"     => $user_data->last_name,
                "redeem_coins" => $subscription_price,
                "user_twitch_id" => $user_twitch_id
            );


            //send mail to user
            $to = $user_data->email;
            $subject = Lang::get("user.twitch_user_subscribe_subject", array(), $this->selected_language) . $mail_user_data['streamer_name'];

            Mail::send($this->selected_language . '.auth.emails.user_twitch_subscription', $mail_user_data, function ($msg) use ($to, $from_email, $from_name, $subject) {
                $msg->to($to)->from($from_email, $from_name)->subject($subject);
            });


            //send mail to admin
            $to =  env("ADMIN_EMAIL", null);
            $twitch_subs_bcc_emails = env("TWITCH_SUBS_BCC_EMAIL", null);
            $bcc_email_name = env("BCC_EMAIL_NAME", null);
            $subject = Lang::get("user.twitch_admin_subscribe_subject", array(), $this->selected_language) . $mail_user_data['user_name'];

            Mail::send($this->selected_language . '.auth.emails.admin_twitch_subscription', $mail_user_data, function ($msg) use ($to, $twitch_subs_bcc_emails, $bcc_email_name, $from_email, $from_name, $subject) {
                $msg->to($to)->bcc($twitch_subs_bcc_emails, $bcc_email_name)->from($from_email, $from_name)->subject($subject);
            });

            $data['subscription_price'] = $subscription_price;
            return $this->sendResponse($data, Lang::get("common.success", array(), $this->selected_language), 200);
        } catch (Exception $e) {
            // return $this->sendError($e->getMessage(), null, 500);
            return $this->sendError(Lang::get("common.something_went_wrong", array(), $this->selected_language), null, 500);
        }
    }

    public function checkReferralLink(Request $request)
    {
        $requestData = $request->json()->all();
        $streamer_name = $requestData['streamer_name'];

        $check_streamer_name = ReferralStreamersName::where('streamer_name', $streamer_name)->first();

        if (empty($check_streamer_name)) {
            return $this->sendError(Lang::get("campaign.link_invalid", array(), $this->selected_language), null, 201);
        } else {
            // get the streamer info
            $get_profile = $this->GetTwitchProfile($streamer_name, 'login');
            return $this->sendResponse($get_profile, Lang::get("common.success", array(), $this->selected_language), 200);
        }
    }

    public function getTopStreamersData(Request $request)
    {
        $top_streamers = DB::select(DB::raw('select description, count(1) as subscriptions, user_twitch_id from user_rewards where reward_id=1 and reward_status="SUCCESS" and created_at >= date(sysdate()) group by description ORDER BY subscriptions DESC limit 10'));


        if ($top_streamers) {
            foreach ($top_streamers as $streamer_data) {
                // get twitch profile
                $get_profile = $this->GetTwitchProfile($streamer_data->description, 'login');
                $profile_image = isset($get_profile[0]) ? $get_profile[0]['profile_image_url'] : '';

                $top_streamers_datas[] = array(
                    "description" => $streamer_data->description,
                    "user_twitch_id" => $streamer_data->user_twitch_id,
                    "subscriptions" => $streamer_data->subscriptions,
                    "streamer_image" => $profile_image
                );
            }
        }

        $total_subs = DB::select(DB::raw('select truncate(sum((sub_total/2)/3.99),0) as total_subs from campaigns where is_start="1" and active="1" and campaign_status="APPROVED" and sysdate() between start_date and end_date'));

        $rewarded_subs = DB::select(DB::raw('select truncate(sum(coins)/664,0) as rewarded_subs from campaigns c inner join campaign_clicks cc on c.id = cc.campaign_id where cc.is_completed="1" and c.is_start="1" and c.active="1" and c.campaign_status="APPROVED" and sysdate() between c.start_date and c.end_date'));

        $not_rewarded_subs = $total_subs[0]->total_subs - $rewarded_subs[0]->rewarded_subs;

        if ($not_rewarded_subs < 0) {
            $not_rewarded_subs = 0;
        }

        $top_streamers_data = [];
        if (!empty($top_streamers_datas)) {
            $top_streamers_data['top_streamers'] = $top_streamers_datas;
        }
        if (!empty($total_subs)) {
            $top_streamers_data['total_subs'] = $total_subs;
        }
        if (!empty($rewarded_subs)) {
            $top_streamers_data['rewarded_subs'] = $rewarded_subs;
        }
        if (!empty($not_rewarded_subs)) {
            $top_streamers_data['not_rewarded_subs'] = $not_rewarded_subs;
        }

        if (!empty($top_streamers_data)) {
            return $this->sendResponse($top_streamers_data, Lang::get("common.success", array(), $this->selected_language), 200);
        } else {
            return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), json_decode("{}"), 201);
        }
    }
}
