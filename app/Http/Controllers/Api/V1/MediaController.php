<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use App\Jobs\MediaPipeline\MediaDeletePipeline;
use App\Jobs\VideoPipeline\VideoThumbnail;
use App\Media;
use App\Services\MediaBlocklistService;
use App\Services\MediaPathService;
use App\Services\UserStorageService;
use App\Transformer\Api\Mastodon\v1\MediaTransformer;
use App\Util\Media\Filter;
use Cache;
use Illuminate\Http\Request;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class MediaController extends Controller
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

    /**
     * POST /api/v1/media
     *
     * @return MediaTransformer
     */
    public function mediaUpload(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $this->validate($request, [
            'file.*' => function () {
                return [
                    'required',
                    'mimes:'.config_cache('pixelfed.media_types'),
                    'max:'.config_cache('pixelfed.max_photo_size'),
                ];
            },
            'filter_name' => 'nullable|string|max:24',
            'filter_class' => 'nullable|alpha_num|max:24',
            'description' => 'nullable|string|max:420',
        ]);

        $user = $request->user();
        $profile = $user->profile;

        if (config_cache('pixelfed.enforce_account_limit') == true) {
            $size = Cache::remember($user->storageUsedKey(), 3600, function () use ($user) {
                return Media::whereUserId($user->id)->sum('size') / 1000;
            });
            $limit = (int) config_cache('pixelfed.max_account_size');
            if ($size >= $limit) {
                abort(403, 'Account size limit reached.');
            }
        }

        $filterClass = in_array($request->input('filter_class'), Filter::classes()) ? $request->input('filter_class') : null;
        $filterName = in_array($request->input('filter_name'), Filter::names()) ? $request->input('filter_name') : null;

        $photo = $request->file('file');

        $mimes = explode(',', config_cache('pixelfed.media_types'));
        if (in_array($photo->getMimeType(), $mimes) == false) {
            abort(403, 'Invalid or unsupported mime type.');
        }

        $storagePath = MediaPathService::get($user, 2);
        $path = $photo->store($storagePath);
        $hash = \hash_file('sha256', $photo);

        abort_if(MediaBlocklistService::exists($hash) == true, 451);

        $media = new Media();
        $media->status_id = null;
        $media->profile_id = $profile->id;
        $media->user_id = $user->id;
        $media->media_path = $path;
        $media->original_sha256 = $hash;
        $media->size = $photo->getSize();
        $media->mime = $photo->getMimeType();
        $media->caption = $request->input('description');
        $media->filter_class = $filterClass;
        $media->filter_name = $filterName;
        $media->save();

        switch ($media->mime) {
            case 'image/jpeg':
            case 'image/jpg':
            case 'image/png':
            case 'image/webp':
            case 'image/heic':
            case 'image/avif':
                ImageOptimize::dispatch($media)->onQueue('mmo');
                break;

            case 'video/mp4':
                VideoThumbnail::dispatch($media)->onQueue('mmo');
                break;
        }

        $fractal = new Fractal\Resource\Item($media, new MediaTransformer());
        $res = $this->fractal->createData($fractal)->toArray();

        return $this->json($res);
    }

    /**
     * PUT /api/v1/media/{id}
     *
     * @return MediaTransformer
     */
    public function mediaUpdate(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $this->validate($request, [
            'description' => 'nullable|string|max:420',
        ]);

        $user = $request->user();
        $media = Media::whereUserId($user->id)->findOrFail($id);
        $media->caption = $request->input('description');
        $media->save();

        $fractal = new Fractal\Resource\Item($media, new MediaTransformer());
        $res = $this->fractal->createData($fractal)->toArray();

        return $this->json($res);
    }

    /**
     * GET /api/v1/media/{id}
     *
     * @return MediaTransformer
     */
    public function mediaGet(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $user = $request->user();
        $media = Media::whereUserId($user->id)->findOrFail($id);

        $fractal = new Fractal\Resource\Item($media, new MediaTransformer());
        $res = $this->fractal->createData($fractal)->toArray();

        return $this->json($res);
    }
}