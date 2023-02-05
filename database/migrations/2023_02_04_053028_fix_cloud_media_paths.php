<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Media;
use App\Services\MediaService;
use App\Services\StatusService;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        ini_set('memory_limit', '-1');
        if(config_cache('pixelfed.cloud_storage') == false) {
            return;
        }

        $disk = Storage::disk(config('filesystems.cloud'));
        $startUrl = $disk->url('test');
        if(!$startUrl) {
            return;
        }
        $baseUrl = substr($startUrl, 0, -4);
        $baseUrlLen = strlen($baseUrl);

        foreach(Media::whereNotNull('cdn_url')->lazyById(200, 'id') as $media) {
            if($media->cdn_url == null) {
                continue;
            }
            $cdnPath = substr($media->cdn_url, $baseUrlLen);
            if(str_starts_with($cdnPath, '/')) {
                continue;
            }
            if(!str_starts_with($cdnPath, 'public/')) {
                continue;
            }
            if($cdnPath != $media->media_path) {
                $media->media_path = $cdnPath;
                $media->saveQuietly();
                if($media->status_id) {
                    MediaService::del($media->status_id);
                    StatusService::del($media->status_id);
                }
            }
        }

        return;
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
