<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignClick extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'brand_id',
        'campaign_id',
        'user_id',
        'is_clicked',
        'is_completed'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

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
}
