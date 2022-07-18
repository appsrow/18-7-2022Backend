<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CampaignFormQuestion extends Model
{
    protected $fillable = [
        'question_type_id',
        'campaign_form_id',
        'question_text',
        'answer_text'
    ];

    protected $casts = [
        'answer_text' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'options',
    ];

    public function getOptionsAttribute()
    {
        while (is_string($this->attributes['answer_text'])) {
            $this->attributes['answer_text'] = json_decode($this->attributes['answer_text'], TRUE);
        }
        $answers = (array) $this->attributes['answer_text'];
        $answers_options = (count($answers) > 0) ? $answers : [];
        return $answers_options;
    }

    public function campaign_form_data()
    {
        return $this->belongsTo(CampaignFormData::class, 'campaign_form_id', 'id');
    }

    public function questionType()
    {
        return $this->belongsTo(QuestionType::class, 'question_type_id', 'id');
    }

    public function user_answers()
    {
        return $this->hasMany(UserFormAnswer::class, 'question_id', 'id');
    }
}
