<?php

namespace App\Models;

use App\HasSnowflakePrimary;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table(incrementing: false)]
class Poll extends Model
{
    use HasFactory, HasSnowflakePrimary;

    protected function casts(): array
    {
        return [
            'poll_options' => 'array',
            'cached_tallies' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function votes()
    {
        return $this->hasMany(PollVote::class);
    }

    public function getTallies()
    {
        return $this->cached_tallies;
    }
}
