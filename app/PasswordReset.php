<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class PasswordReset extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email',
        'token'
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
