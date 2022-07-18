<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TargetType extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'target_type',
        'category',
    ];
    public $timestamps = false;
}
