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

    // To get all moderated instances, we need to search where (banned OR unlisted)


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
}
