<?php

namespace App\Http\Controllers;

use App\DirectMessage;
use App\Jobs\StoryPipeline\StoryDelete;
use App\Jobs\StoryPipeline\StoryFanout;
use App\Jobs\StoryPipeline\StoryReactionDeliver;
use App\Jobs\StoryPipeline\StoryReplyDeliver;
use App\Models\Conversation;
use App\Models\Poll;
use App\Models\PollVote;
use App\Notification;
use App\Report;
use App\Services\FollowerService;
use App\Services\MediaPathService;
use App\Services\StoryIndexService;
use App\Services\StoryService;
use App\Services\UserRoleService;
use App\Status;
use App\Story;
use FFMpeg;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\ImageManager;
use Storage;

class StoryComposeController extends Controller
{
    protected $imageManager;

    public function __construct()
    {
        $driver = match (config('image.driver')) {
            'imagick' => \Intervention\Image\Drivers\Imagick\Driver::class,
            'vips' => \Intervention\Image\Drivers\Vips\Driver::class,
            default => \Intervention\Image\Drivers\Gd\Driver::class
        };
        $this->imageManager = new ImageManager(
            $driver,
            autoOrientation: true,
            decodeAnimation: true,
            blendingColor: 'ffffff',
            strip: true
        );
    }

    protected function storePhoto($photo, $user)
    {
        $mimes = explode(',', config_cache('pixelfed.media_types'));
        if (in_array($photo->getMimeType(), [
            'image/jpeg',
            'image/png',
            'video/mp4',
        ]) == false) {
            abort(400, 'Invalid media type');

            return;
        }

        $storagePath = MediaPathService::story($user->profile);
        $filename = Str::random(random_int(2, 12)).'_'.Str::random(random_int(32, 35)).'_'.Str::random(random_int(1, 14)).'.'.$photo->extension();
        $path = $photo->storePubliclyAs($storagePath, $filename);

        if (in_array($photo->getMimeType(), ['image/jpeg', 'image/jpg', 'image/png'])) {
            $localFs = config('filesystems.default') === 'local';

            if ($localFs) {
                $fpath = storage_path('app/'.$path);

                $img = $this->imageManager->read($fpath);
                $quality = config_cache('pixelfed.image_quality');
                $encoder = in_array($photo->getMimeType(), ['image/jpeg', 'image/jpg']) ?
                    new JpegEncoder($quality) :
                    new PngEncoder;

                $encoded = $img->encode($encoder);
                file_put_contents($fpath, (string) $encoded);
            } else {
                $disk = Storage::disk(config('filesystems.default'));

                $fileContent = $disk->get($path);

                $img = $this->imageManager->read($fileContent);
                $quality = config_cache('pixelfed.image_quality');
                $encoder = in_array($photo->getMimeType(), ['image/jpeg', 'image/jpg']) ?
                    new JpegEncoder($quality) :
                    new PngEncoder;

                $encoded = $img->encode($encoder);

                $disk->put($path, (string) $encoded);
            }
        }

        return $path;
    }
}
