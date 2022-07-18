<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TargetSubtype extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'target_id',
        'subtype_name',
        'minimum_cac',
    ];
    public $timestamps = false;
}
