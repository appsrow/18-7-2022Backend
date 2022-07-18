<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\UserCoinsBalances;

class UserCoins extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'reward_id',
        'campaign_id',
        'transaction_date',
        'opening_balance',
        'credit',
        'debit',
        'closing_balance',
        'comments',
        'paypal_request',
        'paypal_response',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function getCreatedAtAttribute($date)
    {
        if ($date) {
            return \Carbon\Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $date)->format('Y-m-d H:i:s');
        }
    }

    public function getUpdatedAtAttribute($date)
    {
        if ($date) {
            return \Carbon\Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $date)->format('Y-m-d H:i:s');
        }
    }

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* 
    * function for credit coins & update user balance
    */
    public static function creditCoins($params)
    {
        //get last record if any and validate if its redeem operation
        $last_user_closing_balance = UserCoins::select('closing_balance')->where('user_id', $params['user_id'])->latest('id')->first();
        $last_user_closing_balance = (!empty($last_user_closing_balance) && $last_user_closing_balance->closing_balance > 0) ? $last_user_closing_balance->closing_balance : 0;

        $credit_data = array(
            'user_id' => $params['user_id'],
            'user_reward_id' => isset($params['user_reward_id']) ? $params['user_reward_id'] : null,
            'campaign_id' => isset($params['campaign_id']) ? $params['campaign_id'] : null,
            'opening_balance' => $last_user_closing_balance,
            'credit' => $params['credit'],
            'closing_balance' => $last_user_closing_balance + $params['credit'],
            'comments' => isset($params['comments']) ? $params['comments'] : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        );

        $inserted = UserCoins::insert($credit_data);

        if ($inserted) {
            UserCoinsBalances::updateUserBalance($params['user_id']);

            return true;
        }

        return false;
    }

    /* 
    * function for credit or debit coins & update user balance
    */
    public static function debitCoins($params)
    {
        //get last record if any and validate if its redeem operation
        $last_user_closing_balance = UserCoins::select('closing_balance')->where('user_id', $params['user_id'])->latest('id')->first();
        $last_user_closing_balance = (!empty($last_user_closing_balance) && $last_user_closing_balance->closing_balance > 0) ? $last_user_closing_balance->closing_balance : 0;

        if ($last_user_closing_balance < $params['debit']) {
            return false;
        }
        $debit_data = array(
            'user_id' => $params['user_id'],
            'user_reward_id' => isset($params['user_reward_id']) ? $params['user_reward_id'] : null,
            'campaign_id' => isset($params['campaign_id']) ? $params['campaign_id'] : null,
            'opening_balance' => $last_user_closing_balance,
            'debit' => $params['debit'],
            'closing_balance' => $last_user_closing_balance - $params['debit'],
            'comments' => isset($params['comments']) ? $params['comments'] : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        );

        $inserted = UserCoins::insert($debit_data);

        if ($inserted) {
            UserCoinsBalances::updateUserBalance($params['user_id']);

            return true;
        }

        return false;
    }
}
