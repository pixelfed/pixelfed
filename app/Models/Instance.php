<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class Instance extends Model
{
    protected function casts(): array
    {
        return [
            'last_crawled_at' => 'datetime',
            'actors_last_synced_at' => 'datetime',
            'notes' => 'array',
            'nodeinfo_last_fetched' => 'datetime',
            'delivery_next_after' => 'datetime',
        ];
    }

    // To get all moderated instances, we need to search where (banned OR unlisted)
    public function scopeModerated($query): void
    {
        $query->where(function ($query) {
            $query->where('banned', true)->orWhere('unlisted', true);
        });
    }

    public function profiles()
    {
        return $this->hasMany(Profile::class, 'domain', 'domain');
    }

    public function statuses()
    {
        return $this->hasManyThrough(
            Status::class,
            Profile::class,
            'domain',
            'profile_id',
            'domain',
            'id'
        );
    }

    public function reported()
    {
        return $this->hasManyThrough(
            Report::class,
            Profile::class,
            'domain',
            'reported_profile_id',
            'domain',
            'id'
        );
    }

    public function reports()
    {
        return $this->hasManyThrough(
            Report::class,
            Profile::class,
            'domain',
            'profile_id',
            'domain',
            'id'
        );
    }

    public function media()
    {
        return $this->hasManyThrough(
            Media::class,
            Profile::class,
            'domain',
            'profile_id',
            'domain',
            'id'
        );
    }

    public function getUrl()
    {
        return url("/i/admin/instances/show/{$this->id}");
    }
}
