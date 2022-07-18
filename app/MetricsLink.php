<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MetricsLink extends Model
{
    protected $table = 'metrics_link';

    public function getCampaignSharingPasswordAttribute()
    {
        return base64_decode($this->attributes['campaign_sharing_password']);
    }
}
