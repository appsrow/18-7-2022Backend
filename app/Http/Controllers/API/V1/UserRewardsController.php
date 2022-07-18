<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\GeneralHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Lang;
use App\Rewards;
use App\UserCoinsBalances;
use App\GiftCard;
use App\User;
use Exception;
use Illuminate\Support\Facades\DB;
use App\UserRewards;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use App\GiftCardsType;
use App\Log;

class UserRewardsController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | User Rewards Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles all user rewards related functionalites
    */

    /**
     * @index - This function is used to get all rewards list. 
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try{
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "userRewards";
            $Log->save();
        } catch (Exception $e) {
            echo "Pending to create logs. WARN: Action logging has failed.";
        
        }
        try {
            // check authenticate user exist
            $user_id = Auth::id();
            $user_data = User::where('user_type', 2)->where('id', $user_id)->first();
            if (empty($user_data)) {
                return $this->sendError(Lang::get("auth.user_not_found", array(), $this->selected_language), json_decode("{}"), 403);
            }

            $totalRecords = UserRewards::select('user_rewards.*', 'rewards.name')
                ->join('rewards', 'user_rewards.reward_id', '=', 'rewards.id')
                ->where('user_id', $user_id)
                ->orderBy('created_at', 'DESC')
                ->get();
            $user_rewards = array();
            if ($totalRecords->count() > 0) {
                //converted lang status
                // $lang_status = array(
                //     "SUCCESS" => Lang::get("common.paypal_status_success", array(), $this->selected_language),
                //     "PROCESSING" => Lang::get("common.paypal_status_processing", array(), $this->selected_language),
                //     "DENIED" => Lang::get("common.paypal_status_denied", array(), $this->selected_language),
                //     "CANCELED" => Lang::get("common.paypal_status_canceled", array(), $this->selected_language),
                //     "FAILED" => Lang::get("common.paypal_status_failed", array(), $this->selected_language),
                // );


                foreach ($totalRecords as $key => $row) {
                    $ar = array(
                        'id'    => $row->id,
                        'reward_id' => $row->reward_id,
                        'name'    => $row->name,
                        'coins'    => (float) $row->redeem_coins,
                        'description' => ($row->description) ? $row->description : "",
                        'date'    => $row->created_at,
                        'status'    => ($row->payment_history_id) ? $row->reward_status : ""
                    );
                    $user_rewards[] = $ar;
                }
                return $this->sendResponse($user_rewards, Lang::get("common.success", array(), $this->selected_language), 200);
            }

            return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), null, 201);
        } catch (Exception $e) {
            return $this->sendError(Lang::get("common.something_went_wrong", array(), $this->selected_language), null, 500);
        }

        return $this->sendError(Lang::get("common.no_data_found", array(), $this->selected_language), null, 201);
    }

    public function redeemGiftCards(Request $request){
        try{
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "redeemGiftCards";
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

            if (empty($requestData['gift_card_id']) || (empty($requestData['user_email']))) {
                return $this->sendError(Lang::get("common.missing_require_data", array(), $this->selected_language), null, 400);
            }

            //get gift card reward data
            $gift_card_data =  GiftCard::select('*')->where('id', $requestData['gift_card_id'])->where('status', 'AVAILABLE')->first();
           
            if (empty($gift_card_data)) {
                return $this->sendError(Lang::get("campaign.gift_card_not_available", array(), $this->selected_language), null, 201);
            }

            // get request parameters
            $gift_card_id = $requestData['gift_card_id'];

            // get gift card info type
            $gift_card_type = $gift_card_data->type;
            
            $gift_card_type_info = GiftCardsType::select('*')->where('gift_card_type', $gift_card_type)->first();

            if(empty($gift_card_type_info)){
                return $this->sendError(Lang::get("common.gift_card_type_not_found", array(), $this->selected_language), null, 201);
            }
            // check if the card is out of stock
            $get_all_cards_redeemed_today_per_card_type =  GiftCard::select('*')->where('type', $gift_card_type)->where('redeemed_at', date('Y-m-d'))->get();
           
            $count_cards_redeemed_today_per_card_type = count($get_all_cards_redeemed_today_per_card_type);
            $limit_card_per_day = env("LIMIT_OF_CARD_TYPE_PER_DAY", 20);
           
            if($count_cards_redeemed_today_per_card_type >= $limit_card_per_day){
                return $this->sendError(Lang::get("common.out_of_stock", array(), $this->selected_language), null, 201);
            }

            //check user coin balance
            $user_closing_amount = UserCoinsBalances::select('coin_balance')->where('user_id', $user_id)->latest('id')->first();

           
            if ($user_closing_amount->coin_balance < $gift_card_data['price']) {
                return $this->sendError(Lang::get("campaign.user_not_enough_coins", array(), $this->selected_language), null, 201);
            }

            //everything is verfied let's proceed
            //add user reward and store it in user coins
            $user_reward = UserRewards::addUserReward(array(
                "user_id" => $user_id,
                "reward_id" => 3,
                "gift_card_id" => $gift_card_data->id,
                "redeem_coins" => $gift_card_data->price,
                "description" => $gift_card_data->type
            ));

            if (empty($user_reward)) {
                return $this->sendError(Lang::get("campaign.user_redeem_reward_failed", array(), $this->selected_language), null, 201);
            }

            //send mail notification

            $from_email =  env("MAIL_FROM_ADDRESS", null);
            $from_name =  env("MAIL_FROM_NAME", null);

            $mail_user_data = array(
                "gift_card_type" => $gift_card_data->type,
                "user_name"     => ucwords($user_data->first_name . " " . $user_data->last_name),
                "user_email"     => $user_data->email,
                "first_name"     => $user_data->first_name,
                "last_name"     => $user_data->last_name,
                "redeem_coins" => $gift_card_data->price,
                "card_amount" => $gift_card_data->amount,
                "gift_card_code" => $gift_card_data->card_code,
                'redeem_link' => $gift_card_type_info->gift_card_redeem_link,
                'gift_card_image' => $gift_card_type_info->gift_card_image
            );

            //send mail to user
            $to = $requestData['user_email'];
            $subject = Lang::get("user.gift_card_user_subject", array(), $this->selected_language) . $mail_user_data['gift_card_type'];

            Mail::send($this->selected_language . '.auth.emails.gift_card_reward', $mail_user_data, function ($msg) use ($to, $from_email, $from_name, $subject) {
                $msg->to($to)->from($from_email, $from_name)->subject($subject);
            });


            //send mail to admin
            $to =  env("ADMIN_EMAIL", null);
            $subject = Lang::get("user.admin_gift_card_receive_subject", array(), $this->selected_language) . $mail_user_data['user_name'];

            Mail::send($this->selected_language . '.auth.emails.admin_gift_card_mail', $mail_user_data, function ($msg) use ($to, $from_email, $from_name, $subject) {
                $msg->to($to)->from($from_email, $from_name)->subject($subject);
            });

            // Update gift card status from available to used
            $change_status = GiftCard::where('id', $requestData['gift_card_id'])->update(['status' => 'USED', 'redeemed_at' => Carbon::now()]);

            // check the available gift cards for the redeemed card type and if the available cards are less than the limit send and alert email to admin
            $get_available_gift_cards = GiftCard::select('*')->where('type', $gift_card_type)->where('status', 'AVAILABLE')->get();
            $count_available_gift_cards = count($get_available_gift_cards);
            $limit_avaialble_cards = env("LIMIT_AVAIALBLE_CARDS_ALERT",10);
            if($count_available_gift_cards <= $limit_avaialble_cards){
                //send mail to admin
                $from_email =  env("MAIL_FROM_ADDRESS", null);
                $from_name =  env("MAIL_FROM_NAME", null);
                $to =  env("ADMIN_EMAIL", null);
                $subject = Lang::get("user.admin_gift_card_receive_subject", array(), $this->selected_language) . $limit_avaialble_cards;

                $mail_user_data = array(
                    "limit_avaialble_cards" => $limit_avaialble_cards
                );

                Mail::send($this->selected_language . '.auth.emails.admin_alert_gift_card', $mail_user_data, function ($msg) use ($to, $from_email, $from_name, $subject) {
                    $msg->to($to)->from($from_email, $from_name)->subject($subject);
                });
            }
            return $this->sendResponse(array(), Lang::get("common.success", array(), $this->selected_language), 200);
        } catch (Exception $e) {
            return $this->sendError(Lang::get("common.something_went_wrong", array(), $this->selected_language), null, 500);
        }
    }

    public function getGiftCards(Request $requestData){
        try{
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "getGiftCards";
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

        try {
            // check all the gift card codes if the date is expired mark the status to expire
            $get_all_gift_cards_list = GiftCard::select('*')->where('status', 'AVAILABLE')->get();
            foreach($get_all_gift_cards_list as $cards => $record){
                // check expiry date
                if(!empty($record['expiry_date']) && $record['expiry_date'] < Carbon::now()){
                    GiftCard::updateGiftCardStatusToExpire($record['id']);
                }
            }

            $gift_card_data =  GiftCard::select('id','type','status','amount','price')->where('status', 'AVAILABLE')->orderBy('amount', 'ASC')->get()->groupBy('type');
          
            $card = [];
            foreach($gift_card_data as $key => $row){
                foreach($row as $row_data){
                    // get info according to gift card type
                    $gift_card_types = GiftCardsType::where('gift_card_type', $row_data->type)->first();
                    $ar = array(
                        'card_name' => $row_data->type,
                        'card_details' => $row,
                        'gift_card_image' => $gift_card_types->gift_card_image
                    );
                }
                $card[] = $ar;
            }

            if (empty($gift_card_data) || count($gift_card_data) <= 0)  {
                return $this->sendError(Lang::get("campaign.gift_card_not_available", array(), $this->selected_language), null, 201);
            }else{
                return $this->sendResponse($card, Lang::get('common.success', array(), $this->selected_language));
            }
        }
        catch(Exception $e){
            return $this->sendError(Lang::get("common.something_went_wrong", array(), $this->selected_language), null, 500);
        }
    }  

    public function getGiftCardTypeInfo(Request $requestData){
        try{
            $Log = new Log;
            $Log->user_id = Auth::id();
            $Log->action = "getGiftCardTypeInfo";
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
        $requestData = $requestData->json()->all();

        $gift_card_type = $requestData['gift_card_type'];

        try {      
            if (empty($requestData) || count($requestData) <= 0) {
                return $this->sendError(Lang::get("common.request_empty", array(), $this->selected_language), null, 400);
            }

            if (empty($requestData['gift_card_type'])) {
                return $this->sendError(Lang::get("common.missing_require_data", array(), $this->selected_language), null, 400);
            }

            $gift_card_data =   DB::select(DB::raw("SELECT A.* FROM `gift_cards` A JOIN (SELECT price,MIN(id)as id FROM `gift_cards` WHERE type='$gift_card_type' AND `status` = 'AVAILABLE' group by price) B ON A.id=B.id order by price"));

            // set limit of the redeemed gift cards for a day
            $get_all_cards_redeemed_today_per_card_type =  GiftCard::select('*')->where('type', $gift_card_type)->where('redeemed_at', date('Y-m-d'))->get();
            $count_cards_redeemed_today_per_card_type = count($get_all_cards_redeemed_today_per_card_type);

            if (empty($gift_card_data) || count($gift_card_data) <= 0)  {
                return $this->sendError(Lang::get("campaign.gift_card_not_available", array(), $this->selected_language), null, 201);
            }

            $limit_card_per_day = env("LIMIT_OF_CARD_TYPE_PER_DAY", 20);
           
            if($count_cards_redeemed_today_per_card_type >= $limit_card_per_day){
                return $this->sendResponse($gift_card_data, Lang::get('common.out_of_stock', array(), $this->selected_language));
            }
            else{
                return $this->sendResponse($gift_card_data, Lang::get('common.success', array(), $this->selected_language));
            }
        }
        catch(Exception $e){
            return $this->sendError(Lang::get("common.something_went_wrong", array(), $this->selected_language), null, 500);
        }
    }  
}
