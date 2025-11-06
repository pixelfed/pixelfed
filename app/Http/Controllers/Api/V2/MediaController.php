<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use App\Jobs\MediaPipeline\MediaDeletePipeline;
use App\Jobs\VideoPipeline\VideoThumbnail;
use App\Media;
use App\Services\MediaBlocklistService;
use App\Services\MediaPathService;
use App\Services\UserStorageService;
use App\Transformer\Api\Mastodon\v1\MediaTransformer;
use App\User;
use App\UserSetting;
use App\Util\Media\Filter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class MediaController extends Controller
{
    const PF_API_ENTITY_KEY = '_pe';

    public function json($res, $code = 200, $headers = [])
    {
        return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    /**
     * POST /api/v2/media
     *
     *
     * @return MediaTransformer
     */
    public function mediaUploadV2(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $this->validate($request, [
            'file.*' => [
                'required_without:file',
                'mimetypes:'.config_cache('pixelfed.media_types'),
                'max:'.config_cache('pixelfed.max_photo_size'),
            ],
            'file' => [
                'required_without:file.*',
                'mimetypes:'.config_cache('pixelfed.media_types'),
                'max:'.config_cache('pixelfed.max_photo_size'),
            ],
            'filter_name' => 'nullable|string|max:24',
            'filter_class' => 'nullable|alpha_dash|max:24',
            'description' => 'nullable|string|max:'.config_cache('pixelfed.max_altext_length'),
            'replace_id' => 'sometimes',
        ]);

        $user = $request->user();

        if ($user->last_active_at == null) {
            return [];
        }

        if (empty($request->file('file'))) {
            return response('', 422);
        }

        $limitKey = 'compose:rate-limit:media-upload:'.$user->id;
        $limitTtl = now()->addMinutes(15);
        $limitReached = Cache::remember($limitKey, $limitTtl, function () use ($user) {
            $dailyLimit = Media::whereUserId($user->id)->where('created_at', '>', now()->subDays(1))->count();

            return $dailyLimit >= 1250;
        });
        abort_if($limitReached == true, 429);

        $profile = $user->profile;

        $accountSize = UserStorageService::get($user->id);
        abort_if($accountSize === -1, 403, 'Invalid request.');
        $photo = $request->file('file');
        $fileSize = $photo->getSize();
        $sizeInKbs = (int) ceil($fileSize / 1000);
        $updatedAccountSize = (int) $accountSize + (int) $sizeInKbs;

        if ((bool) config_cache('pixelfed.enforce_account_limit') == true) {
            $limit = (int) config_cache('pixelfed.max_account_size');
            if ($updatedAccountSize >= $limit) {
                abort(403, 'Account size limit reached.');
            }
        }

        $filterClass = in_array($request->input('filter_class'), Filter::classes()) ? $request->input('filter_class') : null;
        $filterName = in_array($request->input('filter_name'), Filter::names()) ? $request->input('filter_name') : null;

        $mimes = explode(',', config_cache('pixelfed.media_types'));
        if (in_array($photo->getMimeType(), $mimes) == false) {
            abort(403, 'Invalid or unsupported mime type.');
        }

        $storagePath = MediaPathService::get($user, 2);
        $path = $photo->storePublicly($storagePath);
        $hash = \hash_file('sha256', $photo);
        $license = null;
        $mime = $photo->getMimeType();

        $settings = UserSetting::whereUserId($user->id)->first();

        if ($settings && ! empty($settings->compose_settings)) {
            $compose = $settings->compose_settings;

            if (isset($compose['default_license']) && $compose['default_license'] != 1) {
                $license = $compose['default_license'];
            }
        }

        abort_if(MediaBlocklistService::exists($hash) == true, 451);

        if ($request->has('replace_id')) {
            $rpid = $request->input('replace_id');
            $removeMedia = Media::whereNull('status_id')
                ->whereUserId($user->id)
                ->whereProfileId($profile->id)
                ->where('created_at', '>', now()->subHours(2))
                ->find($rpid);
            if ($removeMedia) {
                MediaDeletePipeline::dispatch($removeMedia)
                    ->onQueue('mmo')
                    ->delay(now()->addMinutes(15));
            }
        }

        $media = new Media;
        $media->status_id = null;
        $media->profile_id = $profile->id;
        $media->user_id = $user->id;
        $media->media_path = $path;
        $media->original_sha256 = $hash;
        $media->size = $photo->getSize();
        $media->mime = $mime;
        $media->caption = $request->input('description');
        $media->filter_class = $filterClass;
        $media->filter_name = $filterName;
        if ($license) {
            $media->license = $license;
        }
        $media->save();

        switch ($media->mime) {
            case 'image/jpg':
            case 'image/jpeg':
            case 'image/png':
            case 'image/webp':
            case 'image/heic':
            case 'image/avif':
                ImageOptimize::dispatch($media)->onQueue('mmo');
                break;

            case 'video/mp4':
                VideoThumbnail::dispatch($media)->onQueue('mmo');
                $preview_url = '/storage/no-preview.png';
                $url = '/storage/no-preview.png';
                break;
        }

        $user->storage_used = (int) $updatedAccountSize;
        $user->storage_used_updated_at = now();
        $user->save();

        Cache::forget($limitKey);
        $fractal = new Fractal\Manager;
        $fractal->setSerializer(new ArraySerializer);
        $resource = new Fractal\Resource\Item($media, new MediaTransformer);
        $res = $fractal->createData($resource)->toArray();
        $res['preview_url'] = $media->url().'?v='.time();
        $res['url'] = null;

        return $this->json($res, 202);
    }
}