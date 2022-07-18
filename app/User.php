<?php

namespace App;

use Illuminate\Notifications\Notifiable as Noifiable;
use App\Notifications\ResetPassword as ResetPasswordNotification;
use App\Notifications\ResetPassword_Brand as ResetPassword_Brands;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements CanResetPasswordContract, JWTSubject
{
    use Noifiable, SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'dob',
        'email',
        'password',
        'gender',
        'city',
        'state',
        'country',
        'phone',
        'user_photo',
        'api_token',
        'active',
        'is_social_sign_in',
        'confirmation_code',
        'confirmation_code_expired',
        'confirmed',
        'created_by',
        'updated_by',
        'user_type',
        'is_deleted',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
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


    protected $dates = ['created_at', 'updated_at'];

    // protected $dateFormat = "Y-m-d";

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'api_token'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function sendPasswordResetNotification_to_brand($token)
    {
        $this->notify(new ResetPassword_Brands($token));
    }

    public function user_rewards_data()
    {
        return $this->hasMany(UserRewards::class, 'user_id', 'id');
    }
}
