<?php

namespace App\Http\Controllers;

use App\Jobs\CommentPipeline\CommentPipeline;
use App\Jobs\StatusPipeline\NewStatusPipeline;
use App\Services\StatusService;
use App\Status;
use App\Transformer\Api\StatusTransformer;
use App\UserFilter;
use Auth;
use DB;
use Illuminate\Http\Request;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use Purify;

class CommentController extends Controller
{
    public function showAll(Request $request, $username, int $id)
    {
        abort(404);
    }

    public function store(Request $request)
    {
        if (Auth::check() === false) {
            abort(403);
        }
        $this->validate($request, [
            'item' => 'required|integer|min:1',
            'comment' => 'required|string|max:'.config_cache('pixelfed.max_caption_length'),
            'sensitive' => 'nullable|boolean',
        ]);
        $comment = $request->input('comment');
        $statusId = $request->input('item');
        $nsfw = $request->input('sensitive', false);

        $user = Auth::user();
        $profile = $user->profile;
        $status = Status::findOrFail($statusId);

        if ($status->comments_disabled == true) {
            return;
        }

        $filtered = UserFilter::whereUserId($status->profile_id)
            ->whereFilterableType('App\Profile')
            ->whereIn('filter_type', ['block'])
            ->whereFilterableId($profile->id)
            ->exists();

        if ($filtered == true) {
            return;
        }

        $reply = DB::transaction(function () use ($comment, $status, $profile, $nsfw) {
            $scope = $profile->is_private == true ? 'private' : 'public';
            $reply = new Status;
            $reply->profile_id = $profile->id;
            $reply->is_nsfw = $nsfw;
            $reply->caption = Purify::clean($comment);
            $reply->rendered = '';
            $reply->in_reply_to_id = $status->id;
            $reply->in_reply_to_profile_id = $status->profile_id;
            $reply->scope = $scope;
            $reply->visibility = $scope;
            $reply->save();

            return $reply;
        });

        StatusService::del($status->id);
        NewStatusPipeline::dispatch($reply);
        CommentPipeline::dispatch($status, $reply);

        if ($request->ajax()) {
            $fractal = new Fractal\Manager;
            $fractal->setSerializer(new ArraySerializer);
            $entity = new Fractal\Resource\Item($reply, new StatusTransformer);
            $entity = $fractal->createData($entity)->toArray();
            $response = [
                'code' => 200,
                'msg' => 'Comment saved',
                'username' => $profile->username,
                'url' => $reply->url(),
                'profile' => $profile->url(),
                'comment' => $reply->caption,
                'entity' => $entity,
            ];
        } else {
            $response = redirect($status->url());
        }

        return $response;
    }
}
