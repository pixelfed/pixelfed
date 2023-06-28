<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Media;
use Illuminate\Support\Facades\Http;
use App\Services\MediaService;
use App\Services\StatusService;

class FetchMissingMediaMimeType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-missing-media-mime-type';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        foreach(Media::whereNotNull(['remote_url', 'status_id'])->whereNull('mime')->lazyByIdDesc(50, 'id') as $media) {
            $res = Http::retry(2, 100, throw: false)->head($media->remote_url);

            if(!$res->successful()) {
                continue;
            }

            if(!in_array($res->header('content-type'), explode(',',config('pixelfed.media_types')))) {
                continue;
            }

            $media->mime = $res->header('content-type');

            if($res->hasHeader('content-length')) {
                $media->size = $res->header('content-length');
            }

            $media->save();

            MediaService::del($media->status_id);
            StatusService::del($media->status_id);
            $this->info('mid:'.$media->id . ' (' . $res->header('content-type') . ':' . $res->header('content-length') . ' bytes)');
        }
    }
}
