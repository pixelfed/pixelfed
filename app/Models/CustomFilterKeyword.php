<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
class CustomFilterKeyword extends Model
{
    protected function casts(): array
    {
        return [
            'whole_word' => 'boolean',
        ];
    }

    public function customFilter(): BelongsTo
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
