<?php

namespace App\Http\Controllers\Api\V1Api;

use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use App\Jobs\MediaPipeline\MediaDeletePipeline;
use App\Jobs\VideoPipeline\VideoThumbnail;
use App\Models\Media as MediaModel;
use App\Models\UserSetting;
use App\Services\AccountService;
use App\Services\MediaBlocklistService;
use App\Services\MediaPathService;
use App\Services\MediaService;
use App\Services\SanitizeService;
use App\Services\StatusService;
use App\Services\UserRoleService;
use App\Services\UserStorageService;
use App\Transformer\Api\Mastodon\v1\MediaTransformer;
use App\Util\Media\Filter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

trait Media
{
    /**
     * POST /api/v1/media
     *
     *
     * @return MediaTransformer
     */
    public function mediaUpload(Request $request)
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
        ]);

        $user = $request->user();
        abort_if($user->has_roles && ! UserRoleService::can('can-post', $user->id), 403, 'Invalid permissions for this action');

        AccountService::setLastActive($user->id);

        if ($user->last_active_at == null) {
            return [];
        }

        if (empty($request->file('file'))) {
            return response('', 422);
        }

        $limitKey = 'compose:rate-limit:media-upload:'.$user->id;
        $limitTtl = now()->addMinutes(15);
        $limitReached = Cache::remember($limitKey, $limitTtl, function () use ($user) {
            $dailyLimit = MediaModel::whereUserId($user->id)->where('created_at', '>', now()->subDays(1))->count();

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

        $hash = \hash_file('sha256', $photo->getRealPath());
        abort_if(MediaBlocklistService::exists($hash) == true, 451);

        $storagePath = MediaPathService::get($user, 2);
        $path = $photo->storePublicly($storagePath);
        $license = null;
        $mime = $photo->getMimeType();

        // if($photo->getMimeType() == 'image/heic') {
        //  abort_if(config('image.driver') !== 'imagick', 422, 'Invalid media type');
        //  abort_if(!in_array('HEIC', \Imagick::queryformats()), 422, 'Unsupported media type');
        //  $oldPath = $path;
        //  $path = str_replace('.heic', '.jpg', $path);
        //  $mime = 'image/jpeg';
        //  \Image::make($photo)->save(storage_path("app/{$path}"));
        //  @unlink(storage_path("app/{$oldPath}"));
        // }

        $settings = UserSetting::whereUserId($user->id)->first();

        if ($settings && ! empty($settings->compose_settings)) {
            $compose = $settings->compose_settings;

            if (isset($compose['default_license']) && $compose['default_license'] != 1) {
                $license = $compose['default_license'];
            }
        }

        $media = new MediaModel;
        $media->status_id = null;
        $media->profile_id = $profile->id;
        $media->user_id = $user->id;
        $media->media_path = $path;
        $media->original_sha256 = $hash;
        $media->size = $photo->getSize();
        $media->mime = $mime;
        $media->caption = $request->input('description') ?? '';
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
        $resource = new Fractal\Resource\Item($media, new MediaTransformer);
        $res = $this->fractal->createData($resource)->toArray();
        $res['preview_url'] = $media->url().'?v='.time();
        $res['url'] = $media->url().'?v='.time();

        return $this->json($res);
    }

    /**
     * PUT /api/v1/media/{id}
     *
     * @param  int  $id
     * @return MediaTransformer
     */
    public function mediaUpdate(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $this->validate($request, [
            'description' => 'nullable|string|max:'.config_cache('pixelfed.max_altext_length'),
        ]);

        $user = $request->user();
        abort_if($user->has_roles && ! UserRoleService::can('can-post', $user->id), 403, 'Invalid permissions for this action');

        AccountService::setLastActive($user->id);

        $media = MediaModel::whereUserId($user->id)
            ->whereProfileId($user->profile_id)
            ->findOrFail($id);

        $executed = RateLimiter::attempt(
            'media:update:'.$user->id,
            10,
            function () use ($media, $request) {
                $caption = app(SanitizeService::class)->html($request->input('description'));

                if ($caption != $media->caption) {
                    $media->caption = $caption;
                    $media->save();

                    if ($media->status_id) {
                        MediaService::del($media->status_id);
                        StatusService::del($media->status_id);
                    }
                }
            }
        );

        if (! $executed) {
            return response()->json([
                'error' => 'Too many attempts. Try again in a few minutes.',
            ], 429);
        }

        $fractal = new Fractal\Manager;
        $fractal->setSerializer(new ArraySerializer);
        $resource = new Fractal\Resource\Item($media, new MediaTransformer);

        return $this->json($fractal->createData($resource)->toArray());
    }

    /**
     * GET /api/v1/media/{id}
     *
     * @param  int  $id
     * @return MediaTransformer
     */
    public function mediaGet(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $user = $request->user();
        abort_if($user->has_roles && ! UserRoleService::can('can-post', $user->id), 403, 'Invalid permissions for this action');
        AccountService::setLastActive($user->id);

        $media = MediaModel::whereUserId($user->id)
            ->whereNull('status_id')
            ->findOrFail($id);

        $resource = new Fractal\Resource\Item($media, new MediaTransformer);
        $res = $this->fractal->createData($resource)->toArray();

        return $this->json($res);
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
        abort_if($user->has_roles && ! UserRoleService::can('can-post', $user->id), 403, 'Invalid permissions for this action');

        if ($user->last_active_at == null) {
            return [];
        }

        AccountService::setLastActive($user->id);

        if (empty($request->file('file'))) {
            return response('', 422);
        }

        $limitKey = 'compose:rate-limit:media-upload:'.$user->id;
        $limitTtl = now()->addMinutes(15);
        $limitReached = Cache::remember($limitKey, $limitTtl, function () use ($user) {
            $dailyLimit = MediaModel::whereUserId($user->id)->where('created_at', '>', now()->subDays(1))->count();

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

        $hash = \hash_file('sha256', $photo->getRealPath());
        abort_if(MediaBlocklistService::exists($hash) == true, 451);

        $storagePath = MediaPathService::get($user, 2);
        $path = $photo->storePublicly($storagePath);
        $license = null;
        $mime = $photo->getMimeType();

        $settings = UserSetting::whereUserId($user->id)->first();

        if ($settings && ! empty($settings->compose_settings)) {
            $compose = $settings->compose_settings;

            if (isset($compose['default_license']) && $compose['default_license'] != 1) {
                $license = $compose['default_license'];
            }
        }

        if ($request->has('replace_id')) {
            $rpid = $request->input('replace_id');
            $removeMedia = MediaModel::whereNull('status_id')
                ->whereUserId($user->id)
                ->whereProfileId($profile->id)
                ->where('created_at', '>', now()->subHours(2))
                ->find($rpid);
            if ($removeMedia) {
                $dateTime = Carbon::now();
                MediaDeletePipeline::dispatch($removeMedia)
                    ->onQueue('mmo')
                    ->delay($dateTime->addMinutes(15));
            }
        }

        $media = new MediaModel;
        $media->status_id = null;
        $media->profile_id = $profile->id;
        $media->user_id = $user->id;
        $media->media_path = $path;
        $media->original_sha256 = $hash;
        $media->size = $photo->getSize();
        $media->mime = $mime;
        $media->caption = $request->input('description') ?? '';
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
        $resource = new Fractal\Resource\Item($media, new MediaTransformer);
        $res = $this->fractal->createData($resource)->toArray();
        $res['preview_url'] = $media->url().'?v='.time();
        $res['url'] = null;

        return $this->json($res, 202);
    }
}
