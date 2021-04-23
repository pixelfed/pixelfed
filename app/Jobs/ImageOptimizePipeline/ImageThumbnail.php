<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Jobs\ImageOptimizePipeline;

use App\Media;
use App\Util\Media\Image;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImageThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $media;

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
    public function __construct(Media $media)
    {
        $this->media = $media;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $media = $this->media;
        if(!$media) {
            return;
        }
        $path = storage_path('app/'.$media->media_path);
        if (!is_file($path)) {
            return;
        }

        try {
            $img = new Image();
            $img->resizeThumbnail($media);
        } catch (Exception $e) {
        }

        $media->processed_at = Carbon::now();
        $media->save();

        ImageUpdate::dispatch($media);
    }
}
