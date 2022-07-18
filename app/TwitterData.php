<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TwitterData extends Model
{
    protected $table = 'twitter_data';

    protected $fillable = [
        'oauth_token',
        'oauth_token_secret',
        'user_id',
        'target_screen_name',
        'target_user_id',
        'status',
        'response'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
}
