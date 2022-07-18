<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentHistory extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "user_id",
        "campaign_id",
        "transaction_id",
        "transaction_date",
        "transaction_type",
        "transaction_status",
        "paypal_id",
        "paypal_reference_number",
        "campaing_status",
        "payment_status",
        "grand_total",
        "paypal_response",
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'transaction_date' => 'datetime'
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

    public static function savePaymentLog($params)
    {
        $payment = new PaymentHistory;
        $payment->user_id = $params['user_id'];
        $payment->campaign_id = isset($params['campaign_id']) ? $params['campaign_id'] : null;
        $payment->rewards_id = isset($params['rewards_id']) ? $params['rewards_id'] : null;
        $payment->invoice_id = isset($params['invoice_id']) ? $params['invoice_id'] : null;
        $payment->transaction_id = isset($params['transaction_id']) ? $params['transaction_id'] : null;
        $payment->transaction_date = isset($params['transaction_date']) ? $params['transaction_date'] : date('Y-m-d');
        $payment->transaction_type = isset($params['transaction_type']) ? $params['transaction_type'] : null;
        $payment->transaction_status = isset($params['transaction_status']) ? $params['transaction_status'] : null;
        $payment->payment_mode = isset($params['payment_mode']) ? $params['payment_mode'] : null;
        $payment->paypal_id = isset($params['paypal_id']) ? $params['paypal_id'] : null;
        $payment->paypal_reference_number = isset($params['paypal_reference_number']) ? $params['paypal_reference_number'] : null;
        $payment->grand_total = isset($params['grand_total']) ? $params['grand_total'] : 0;
        $payment->paypal_request = isset($params['paypal_request']) ? $params['paypal_request'] : null;
        $payment->paypal_response = isset($params['paypal_response']) ? $params['paypal_response'] : null;
        $payment->created_at = date('Y-m-d H:i:s');
        $payment->updated_at = date('Y-m-d H:i:s');
        $payment->save();
        if ($payment) {
            return $payment->id;
        }

        return false;
    }
}
