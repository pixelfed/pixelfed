<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Instance extends Model
{
    protected $casts = [
        'last_crawled_at' => 'datetime',
        'actors_last_synced_at' => 'datetime',
        'notes' => 'array',
        'nodeinfo_last_fetched' => 'datetime',
        'delivery_next_after' => 'datetime',
    ];

    protected $fillable = [
        'domain',
        'banned',
        'auto_cw',
        'unlisted',
        'notes'
    ];

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
