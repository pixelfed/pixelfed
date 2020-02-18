<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use DB;
use Cache;

use App\Comment;
use App\Jobs\CommentPipeline\CommentPipeline;
use App\Jobs\StatusPipeline\NewStatusPipeline;
use App\Util\Lexer\Autolink;
use App\Profile;
use App\Status;
use App\UserFilter;
use League\Fractal;
use App\Transformer\Api\StatusTransformer;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

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
            'item'    => 'required|integer|min:1',
            'comment' => 'required|string|max:'.(int) config('pixelfed.max_caption_length'),
        ]);
        $comment = $request->input('comment');
        $statusId = $request->item;

        $user = Auth::user();
        $profile = $user->profile;
        $status = Status::findOrFail($statusId);

        if($status->comments_disabled == true) {
            return;
        }

        $filtered = UserFilter::whereUserId($status->profile_id)
            ->whereFilterableType('App\Profile')
            ->whereIn('filter_type', ['block'])
            ->whereFilterableId($profile->id)
            ->exists();

        if($filtered == true) {
            return;
        }

        $reply = DB::transaction(function() use($comment, $status, $profile) {
            $scope = $profile->is_private == true ? 'private' : 'public';
            $autolink = Autolink::create()->autolink($comment);
            $reply = new Status();
            $reply->profile_id = $profile->id;
            $reply->caption = e($comment);
            $reply->rendered = $autolink;
            $reply->in_reply_to_id = $status->id;
            $reply->in_reply_to_profile_id = $status->profile_id;
            $reply->scope = $scope;
            $reply->visibility = $scope;
            $reply->save();

            $status->reply_count++;
            $status->save();

            return $reply;
        });

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
