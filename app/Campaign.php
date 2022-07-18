<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'campaign_name',
        'goal_of_campaign',
        'start_date',
        'end_date',
        'product_information',
        'cac',
        'total_budget',
        'coins',
        'user_target',
        'campaign_image',
        'uploaded_video_url',
        'selected_social_media_name',
        'selected_social_media_url',
        'app_download_link',
        'campaign_status',
        'is_approved',
        'note',
        'website_url',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $dates = [
        'deleted_at'
    ];

    public function companyInfo()
    {
        return $this->belongsTo(Company::class, 'company_id');
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

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];
}
