<?php

namespace App\Http\Controllers\Api\V1Dot1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StatusController as BaseStatusController;
use App\Http\Resources\StatusStateless;
use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use App\Jobs\StatusPipeline\NewStatusPipeline;
use App\Jobs\VideoPipeline\VideoThumbnail;
use App\Media;
use App\Services\BouncerService;
use App\Services\MediaBlocklistService;
use App\Services\MediaPathService;
use App\Services\SanitizeService;
use App\Services\StatusService;
use App\Services\UserStorageService;
use App\Status;
use App\StatusArchived;
use App\User;
use App\UserSetting;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class StatusController extends Controller
{
    protected $fractal;

    public function __construct()
    {
        $this->fractal = new Fractal\Manager;
        $this->fractal->setSerializer(new ArraySerializer);
    }

    public function json($res, $code = 200, $headers = [])
    {
        return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    public function error($msg, $code = 400, $extra = [], $headers = [])
    {
        $res = [
            'msg' => $msg,
            'code' => $code,
        ];

        return response()->json(array_merge($res, $extra), $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    /**
     * POST /api/v1.1/status/create
     *
     *
     * @return StatusTransformer
     */
    public function statusCreate(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $this->validate($request, [
            'status' => 'nullable|string|max:'.(int) config_cache('pixelfed.max_caption_length'),
            'file' => [
                'required',
                'file',
                'mimetypes:'.config_cache('pixelfed.media_types'),
                'max:'.config_cache('pixelfed.max_photo_size'),
                function ($attribute, $value, $fail) {
                    if (is_array($value) && count($value) > 1) {
                        $fail('Only one file can be uploaded at a time.');
                    }
                },
            ],
            'sensitive' => 'nullable',
            'visibility' => 'string|in:private,unlisted,public',
            'spoiler_text' => 'sometimes|max:140',
        ]);

        if ($request->hasHeader('idempotency-key')) {
            $key = 'pf:api:v1:status:idempotency-key:'.$request->user()->id.':'.hash('sha1', $request->header('idempotency-key'));
            $exists = Cache::has($key);
            abort_if($exists, 400, 'Duplicate idempotency key.');
            Cache::put($key, 1, 3600);
        }

        if (config('costar.enabled') == true) {
            $blockedKeywords = config('costar.keyword.block');
            if ($blockedKeywords !== null && $request->status) {
                $keywords = config('costar.keyword.block');
                foreach ($keywords as $kw) {
                    if (Str::contains($request->status, $kw) == true) {
                        abort(400, 'Invalid object. Contains banned keyword.');
                    }
                }
            }
        }
        $user = $request->user();

        if ($user->has_roles) {
            abort_if(! \App\Services\UserRoleService::can('can-post', $user->id), 403, 'Invalid permissions for this action');
        }

        $profile = $user->profile;

        $limitKey = 'compose:rate-limit:media-upload:'.$user->id;
        $photo = $request->file('file');
        $fileSize = $photo->getSize();
        $sizeInKbs = (int) ceil($fileSize / 1000);
        $accountSize = UserStorageService::get($user->id);
        abort_if($accountSize === -1, 403, 'Invalid request.');
        $updatedAccountSize = (int) $accountSize + (int) $sizeInKbs;

        if ((bool) config_cache('pixelfed.enforce_account_limit') == true) {
            $limit = (int) config_cache('pixelfed.max_account_size');
            if ($updatedAccountSize >= $limit) {
                abort(403, 'Account size limit reached.');
            }
        }

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

        $visibility = $profile->is_private ? 'private' : (
            $profile->unlisted == true &&
            $request->input('visibility', 'public') == 'public' ?
            'unlisted' :
            $request->input('visibility', 'public'));

        if ($user->last_active_at == null) {
            return [];
        }
        $defaultCaption = '';
        $cleanedStatus = app(SanitizeService::class)->html($request->input('status', ''));
        $content = $request->filled('status') ? strip_tags($cleanedStatus) : $defaultCaption;
        $cw = $user->profile->cw == true ? true : $request->boolean('sensitive', false);
        $spoilerText = $cw && $request->filled('spoiler_text') ? $request->input('spoiler_text') : null;

        $status = new Status;
        $status->caption = $content;
        $status->rendered = $defaultCaption;
        $status->profile_id = $user->profile_id;
        $status->is_nsfw = $cw;
        $status->cw_summary = $spoilerText;
        $status->scope = $visibility;
        $status->visibility = $visibility;
        $status->type = BaseStatusController::mimeTypeCheck([$mime]);
        $status->save();

        if (! $status) {
            abort(500, 'An error occured.');
        }

        $media = new Media;
        $media->status_id = $status->id;
        $media->profile_id = $profile->id;
        $media->user_id = $user->id;
        $media->media_path = $path;
        $media->original_sha256 = $hash;
        $media->size = $photo->getSize();
        $media->mime = $mime;
        $media->order = 1;
        $media->caption = $request->input('description');
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

        NewStatusPipeline::dispatch($status);

        Cache::forget('user:account:id:'.$user->id);
        Cache::forget('_api:statuses:recent_9:'.$user->profile_id);
        Cache::forget('profile:status_count:'.$user->profile_id);
        Cache::forget($user->storageUsedKey());
        Cache::forget('profile:embed:'.$status->profile_id);
        Cache::forget($limitKey);

        $res = StatusService::getMastodon($status->id, false);
        $res['favourited'] = false;
        $res['language'] = 'en';
        $res['bookmarked'] = false;
        $res['card'] = null;

        return $this->json($res);
    }
}