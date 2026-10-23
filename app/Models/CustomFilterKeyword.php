<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $custom_filter_id
 * @property string $keyword
 * @property bool $whole_word
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CustomFilter $customFilter
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterKeyword newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterKeyword newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterKeyword query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterKeyword whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterKeyword whereCustomFilterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterKeyword whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterKeyword whereKeyword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterKeyword whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFilterKeyword whereWholeWord($value)
 *
 * @mixin \Eloquent
 */
class CustomFilterKeyword extends Model
{
    protected $guarded = [];

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

    public function toRegex(): string
    {
        $pattern = preg_quote($this->keyword, '/');

        if ($this->whole_word) {
            $pattern = '\b'.$pattern.'\b';
        }

        return '/'.$pattern.'/i';
    }
}
