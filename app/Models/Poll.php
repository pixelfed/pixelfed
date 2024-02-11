<?php

namespace App\Models;

use App\HasSnowflakePrimary;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poll extends Model
{
    use HasFactory, HasSnowflakePrimary;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $casts = [
        'poll_options' => 'array',
        'cached_tallies' => 'array',
        'expires_at' => 'datetime',
    ];

    public function votes()
    {
        return $this->hasMany(PollVote::class);
    }

    public function getTallies()
    {
        return $this->cached_tallies;
    }
}
