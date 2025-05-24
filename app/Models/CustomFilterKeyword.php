<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomFilterKeyword extends Model
{
    protected $fillable = [
        'keyword', 'whole_word', 'custom_filter_id',
    ];

    protected $casts = [
        'whole_word' => 'boolean',
    ];

    public function customFilter()
    {
        return $this->belongsTo(CustomFilter::class);
    }

    public function setKeywordAttribute($value)
    {
        $this->attributes['keyword'] = mb_strtolower(trim($value));
    }

    public function toRegex()
    {
        $pattern = preg_quote($this->keyword, '/');

        if ($this->whole_word) {
            $pattern = '\b'.$pattern.'\b';
        }

        return '/'.$pattern.'/i';
    }
}
