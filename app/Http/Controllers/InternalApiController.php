<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{
    Like,
    Media,
    Profile,
    Status,
};
use Auth,Cache;
use League\Fractal;
use App\Transformer\Api\{
    AccountTransformer,
    StatusTransformer,
};
use App\Jobs\StatusPipeline\NewStatusPipeline;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

class InternalApiController extends Controller
{
    protected $fractal;

    public function __construct()
    {
        $this->middleware('auth');
        $this->fractal = new Fractal\Manager();
        $this->fractal->setSerializer(new ArraySerializer());
    }

    public function status(Request $request, $username, int $postid)
    {
        $auth = Auth::user()->profile;
        $profile = Profile::whereUsername($username)->first();
        $status = Status::whereProfileId($profile->id)->find($postid);
        $status = new Fractal\Resource\Item($status, new StatusTransformer());
        $user = new Fractal\Resource\Item($auth, new AccountTransformer());
        $res = [];
        $res['status'] = $this->fractal->createData($status)->toArray();
        $res['user'] = $this->fractal->createData($user)->toArray();
        return response()->json($res, 200, [], JSON_PRETTY_PRINT);
    }

    public function statusComments(Request $request, $username, int $postId)
    {
        $this->validate($request, [
            'min_id'    => 'nullable|integer|min:1',
            'max_id'    => 'nullable|integer|min:1|max:'.PHP_INT_MAX,
            'limit'     => 'nullable|integer|min:5|max:50'
        ]);
        $limit = $request->limit ?? 10;
        $auth = Auth::user()->profile;
        $profile = Profile::whereUsername($username)->first();
        $status = Status::whereProfileId($profile->id)->find($postId);
        if($request->filled('min_id') || $request->filled('max_id')) {
            $q = false;
            $limit = 50;
            if($request->filled('min_id')) {
                $replies = $status->comments()
                ->select('id', 'caption', 'rendered', 'profile_id', 'created_at')
                ->where('id', '>=', $request->min_id)
                ->orderBy('id', 'desc')
                ->paginate($limit);
            }
            if($request->filled('max_id')) {
                $replies = $status->comments()
                ->select('id', 'caption', 'rendered', 'profile_id', 'created_at')
                ->where('id', '<=', $request->max_id)
                ->orderBy('id', 'desc')
                ->paginate($limit);
            }
        } else {
            $replies = $status->comments()
            ->select('id', 'caption', 'rendered', 'profile_id', 'created_at')
            ->orderBy('id', 'desc')
            ->paginate($limit);
        }

        $resource = new Fractal\Resource\Collection($replies, new StatusTransformer(), 'data');
        $resource->setPaginator(new IlluminatePaginatorAdapter($replies));
        $res = $this->fractal->createData($resource)->toArray();
        return response()->json($res, 200, [], JSON_PRETTY_PRINT);
    }

    public function compose(Request $request)
    {
        $this->validate($request, [
            'caption' => 'nullable|string',
            'media.*'   => 'required',
            'media.*.id' => 'required|integer|min:1',
            'media.*.filter' => 'nullable|string|max:30',
            'media.*.license' => 'nullable|string|max:80',
            'visibility' => 'required|string|in:public,private|min:2|max:10'
        ]);

        $profile = Auth::user()->profile;
        $visibility = $request->input('visibility');
        $medias = $request->input('media');
        $attachments = [];
        $status = new Status;

        foreach($medias as $media) {
            $m = Media::findOrFail($media['id']);
            if($m->profile_id !== $profile->id || $m->status_id) {
                abort(403, 'Invalid media id');
            }
            $m->filter_class = $media['filter'];
            $m->license = $media['license'];
            if($media['cw'] == true) {
                $m->is_nsfw = true;
                $status->is_nsfw = true;
            }
            $m->save();
            $attachments[] = $m;
        }

        $status->caption = strip_tags($request->caption);
        $status->visibility = 'draft';
        $status->scope = 'draft';
        $status->profile_id = $profile->id;
        $status->save();

        foreach($attachments as $media) {
            $media->status_id = $status->id;
            $media->save();
        }

        $status->visibility = $visibility;
        $status->scope = $visibility;
        $status->save();

        NewStatusPipeline::dispatch($status);

        return $status->url();
    }
}
