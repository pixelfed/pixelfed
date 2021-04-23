<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Jobs\FollowPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Cache, Log;
use Illuminate\Support\Facades\Redis;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use App\FollowRequest;
use App\Util\ActivityPub\Helpers;
use App\Transformer\ActivityPub\Verb\Follow;

class FollowActivityPubDeliver implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $followRequest;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(FollowRequest $followRequest)
    {
        $this->followRequest = $followRequest;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $follow = $this->followRequest;
        $actor = $follow->actor;
        $target = $follow->target;

        if($target->domain == null || $target->inbox_url == null || !$actor->private_key) {
        	return;
        }

        $fractal = new Fractal\Manager();
        $fractal->setSerializer(new ArraySerializer());
        $resource = new Fractal\Resource\Item($follow, new Follow());
        $activity = $fractal->createData($resource)->toArray();
        $url = $target->sharedInbox ?? $target->inbox_url;
        
        Helpers::sendSignedObject($actor, $url, $activity);
    }
}
