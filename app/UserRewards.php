<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\UserCoins;
use App\Rewards;

class UserRewards extends Model
{
    /*
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_rewards';

    /* 
    * function for add rewards
    */
    public static function addUserReward($params)
    {

        if (!empty($params['reward_id']) && !empty($params['user_id']) && !empty($params['redeem_coins'])) {

            // get reward amount to store the amount
            $get_reward = Rewards::where('id', $params['reward_id'])->first();
            $reward_cost = $get_reward->amount;
            $reward_id = $get_reward->id;

            //get last record if any and validate if its redeem operation
            $UserRewards = new UserRewards;

            $UserRewards->user_id = $params['user_id'];
            $UserRewards->reward_id = (isset($params['reward_id'])) ? $params['reward_id'] : null;
            $UserRewards->gift_card_id = (isset($params['gift_card_id'])) ? $params['gift_card_id'] : null;

            $UserRewards->redeem_coins = $params['redeem_coins'];
            $UserRewards->real_cost = $reward_cost ? $reward_cost : 0;
            $UserRewards->description = isset($params['description']) ? $params['description'] : null;
            $UserRewards->payment_history_id = isset($params['payment_history_id']) ? $params['payment_history_id'] : null;
            $UserRewards->user_twitch_id = isset($params['user_twitch_id']) ? $params['user_twitch_id'] : null;
            $UserRewards->streamer_twitch_id = isset($params['streamer_twitch_id']) ? $params['streamer_twitch_id'] : null;
            if ($reward_id != 2 && $reward_id != 1) {
                $UserRewards->reward_status =  'SUCCESS';
            }
            // $UserRewards->reward_status = "SUCCESS";
            $UserRewards->save();
            if ($UserRewards) {
                //inserted successfully update user coins table

                //do process of coins deduction
                $user_coin_deduction = UserCoins::debitCoins(array(
                    'user_id' => $params['user_id'],
                    'user_reward_id' => $UserRewards->id, // new user reward id
                    'debit' => $params['redeem_coins']
                ));

                return $UserRewards->id;
            }
        }

        return false;
    }
}
