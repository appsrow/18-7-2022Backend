<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CampaignFormData extends Model
{
    protected $table = 'campaign_forms_data';

    protected $fillable = [
        'campaign_id',
        'form_name',
        'description'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'id');
    }

    public function questions()
    {
        return $this->hasMany(CampaignFormQuestion::class, 'campaign_form_id', 'id')->orderBy('question_type_id');
    }
}
