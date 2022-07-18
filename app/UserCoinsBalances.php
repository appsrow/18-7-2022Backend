<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\UserCoins;
use Illuminate\Support\Facades\DB;

class UserCoinsBalances extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'earned_coins',
        'redeem_coins',
        'coin_balance',
        'amount',
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
            return Carbon::createFromFormat('Y-m-d H:i:s', $date)->format('Y-m-d H:i:s');
        }
    }

    public function getUpdatedAtAttribute($date)
    {
        if ($date) {
            return Carbon::createFromFormat('Y-m-d H:i:s', $date)->format('Y-m-d H:i:s');
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
    * function for update user balance
    */
    public static function updateUserBalance($user_id)
    {
        $total_balance = UserCoins::selectRaw('SUM(credit) as earn, SUM(debit) as redeem,(SUM(credit)-SUM(debit)) as balance')->groupBy('user_id')->where('user_id', $user_id)->first();
        if (!empty($total_balance)) {
            $user_data = UserCoinsBalances::select('*')->where('user_id', $user_id)->latest('id')->first();

            if (empty($user_data)) {
                $user_data = new UserCoinsBalances; //creating object for insert
            }
            $user_data->user_id = isset($user_data->user_id) ? $user_data->user_id : $user_id;
            $user_data->earned_coins = $total_balance->earn;
            $user_data->redeem_coins = $total_balance->redeem;
            $user_data->coin_balance = $total_balance->balance;
            $user_data->save();
        }
    }

    /* 
    * function for get user wallet balance
    */
    public static function getUserBalance($user_id)
    {
        $balance = 0;
        $closing_amount = UserCoinsBalances::where('user_id', $user_id)->latest('id')->first();
        if (!empty($closing_amount)) {
            $balance = $closing_amount->coin_balance;
        }

        return $balance;
    }
}
