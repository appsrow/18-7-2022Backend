<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\BrandWallet;
use Illuminate\Support\Facades\DB;

class BrandWalletBalance extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'paid_euro',
        'reduce_euro',
        'euro_balance',
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
    * function for update brand balance
    */
    public static function updateBrandBalance($user_id)
    {
        $total_balance = BrandWallet::selectRaw('SUM(credit) as credit, SUM(debit) as debit,(SUM(credit)-SUM(debit)) as balance')->groupBy('user_id')->where('user_id', $user_id)->first();
        if (!empty($total_balance)) {
            $user_data = BrandWalletBalance::select('*')->where('user_id', $user_id)->latest('id')->first();

            if (empty($user_data)) {
                $user_data = new BrandWalletBalance; //creating object for insert
            }
            $user_data->user_id = isset($user_data->user_id) ? $user_data->user_id : $user_id;
            $user_data->paid_euro = $total_balance->credit;
            $user_data->reduce_euro = $total_balance->debit;
            $user_data->euro_balance = $total_balance->balance;
            $user_data->save();
        }
    }

    /* 
    * function for get total brand wallet balance
    */
    public static function getBrandBalance($user_id)
    {
        $balance = 0;
        $closing_amount = BrandWalletBalance::where('user_id', $user_id)->latest('id')->first();
        if (!empty($closing_amount)) {
            $balance = $closing_amount->euro_balance;
        }

        return $balance;
    }
}
