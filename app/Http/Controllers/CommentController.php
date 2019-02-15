<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Cache;

use App\Comment;
use App\Jobs\CommentPipeline\CommentPipeline;
use App\Jobs\StatusPipeline\NewStatusPipeline;
use App\Profile;
use App\Status;
use League\Fractal;
use App\Transformer\Api\StatusTransformer;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

class CommentController extends Controller
{
    public function show(Request $request, $username, int $id, int $cid)
    {
        $user = Profile::whereUsername($username)->firstOrFail();
        $status = Status::whereProfileId($user->id)->whereInReplyToId($id)->findOrFail($cid);

        return view('status.reply', compact('user', 'status'));
    }

    public function showAll(Request $request, $username, int $id)
    {
        $user = Profile::whereUsername($username)->firstOrFail();
        $status = Status::whereProfileId($user->id)->findOrFail($id);
        $replies = Status::whereInReplyToId($id)->paginate(40);

        return view('status.comments', compact('user', 'status', 'replies'));
    }

    public function store(Request $request)
    {
        if (Auth::check() === false) {
            abort(403);
        }
        $this->validate($request, [
            'item'    => 'required|integer',
            'comment' => 'required|string|max:500',
        ]);
        $comment = $request->input('comment');
        $statusId = $request->item;

        $user = Auth::user();
        $profile = $user->profile;
        $status = Status::findOrFail($statusId);

        Cache::forget('transform:status:'.$status->url());

        $reply = new Status();
        $reply->profile_id = $profile->id;
        $reply->caption = e($comment);
        $reply->rendered = $comment;
        $reply->in_reply_to_id = $status->id;
        $reply->in_reply_to_profile_id = $status->profile_id;
        $reply->save();

        NewStatusPipeline::dispatch($reply, false);
        CommentPipeline::dispatch($status, $reply);

        if ($request->ajax()) {
            $fractal = new Fractal\Manager();
            $fractal->setSerializer(new ArraySerializer());
            $entity = new Fractal\Resource\Item($reply, new StatusTransformer());
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
