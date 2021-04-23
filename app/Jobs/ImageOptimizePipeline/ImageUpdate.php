<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Jobs\ImageOptimizePipeline;

use Storage;
use App\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ImageOptimizer;
use Illuminate\Http\File;
use App\Services\MediaPathService;
use App\Jobs\MediaPipeline\MediaStoragePipeline;

class ImageUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $media;

    protected $protectedMimes = [
        'image/jpeg',
        'image/png',
    ];

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
        $thumb = storage_path('app/'.$media->thumbnail_path);
        
        if (!is_file($path)) {
            return;
        }

        if (in_array($media->mime, $this->protectedMimes) == true) {
            ImageOptimizer::optimize($thumb);
            if(!$media->skip_optimize) {
                ImageOptimizer::optimize($path);
            }
        }

        if (!is_file($path) || !is_file($thumb)) {
            return;
        }

        $photo_size = filesize($path);
        $thumb_size = filesize($thumb);
        $total = ($photo_size + $thumb_size);
        $media->size = $total;
        $media->save();

        MediaStoragePipeline::dispatch($media);

    }
}
