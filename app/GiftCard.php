<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class GiftCard extends Model
{
    protected $table = 'gift_cards';

    protected $fillable = ["card_code","type","amount","price","expiry_date"];

    public static function updateGiftCardStatusToExpire($gift_card_id)
    {
        $change_status = GiftCard::where('id', $gift_card_id)->update(['status' => 'EXPIRED']);
    }

    public function getCardCodeAttribute()
    {
        return Crypt::decryptString($this->attributes['card_code']);
    }

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
}

