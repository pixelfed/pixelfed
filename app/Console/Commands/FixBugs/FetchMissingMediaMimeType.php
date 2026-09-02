<?php

namespace App\Console\Commands\FixBugs;

use App\Models\Media;
use App\Services\MediaService;
use App\Services\StatusService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('app:fetch-missing-media-mime-type')]
#[Description('Command description')]
class FetchMissingMediaMimeType extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        foreach (Media::whereNotNull(['remote_url', 'status_id'])->whereNull('mime')->lazyByIdDesc(50, 'id') as $media) {
            $res = Http::retry(2, 100, throw: false)->head($media->remote_url);

            if (! $res->successful()) {
                continue;
            }

            if (! in_array($res->header('content-type'), explode(',', config_cache('pixelfed.media_types')))) {
                continue;
            }

            $media->mime = $res->header('content-type');

            if ($res->hasHeader('content-length')) {
                $media->size = $res->header('content-length');
            }

            $media->save();

            MediaService::del($media->status_id);
            StatusService::del($media->status_id);
            $this->info('mid:'.$media->id.' ('.$res->header('content-type').':'.$res->header('content-length').' bytes)');
        }
    }
}
