<?php

namespace App\Http\Controllers\Groups;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AccountService;
use App\Services\GroupService;
use App\Services\Groups\GroupCommentService;
use App\Services\Groups\GroupMediaService;
use App\Services\Groups\GroupPostService;
use App\Services\Groups\GroupsLikeService;
use App\Models\Group;
use App\Models\GroupLike;
use App\Models\GroupMedia;
use App\Models\GroupPost;
use App\Models\GroupComment;
use Purify;
use App\Util\Lexer\Autolink;
use App\Jobs\GroupsPipeline\ImageResizePipeline;
use App\Jobs\GroupsPipeline\ImageS3UploadPipeline;
use App\Jobs\GroupsPipeline\NewPostPipeline;
use App\Jobs\GroupsPipeline\NewCommentPipeline;
use App\Jobs\GroupsPipeline\DeleteCommentPipeline;

class GroupsCommentController extends Controller
{
    public function getComments(Request $request)
    {
        $this->validate($request, [
            'gid' => 'required',
            'sid' => 'required',
            'cid' => 'sometimes',
            'limit' => 'nullable|integer|min:3|max:10'
        ]);

        $pid = optional($request->user())->profile_id;
        $gid = $request->input('gid');
        $sid = $request->input('sid');
        $cid = $request->has('cid') && $request->input('cid') == 1;
        $limit = $request->input('limit', 3);
        $maxId = $request->input('max_id', 0);

        $group = Group::findOrFail($gid);

        abort_if($group->is_private && !$group->isMember($pid), 403, 'Not a member of group.');

        $status = $cid ? GroupComment::findOrFail($sid) : GroupPost::findOrFail($sid);

        abort_if($status->group_id != $group->id, 400, 'Invalid group');

        $replies = GroupComment::whereGroupId($group->id)
            ->whereStatusId($status->id)
            ->orderByDesc('id')
            ->when($maxId, function($query, $maxId) {
                return $query->where('id', '<', $maxId);
            })
            ->take($limit)
            ->get()
            ->map(function($gp) use($pid) {
                $status = GroupCommentService::get($gp['group_id'], $gp['id']);
                $status['reply_count'] = $gp['reply_count'];
                $status['url'] = $gp->url();
                $status['favourited'] = (bool) GroupsLikeService::liked($pid, $gp['id']);
                $status['account']['url'] = url("/groups/{$gp['group_id']}/user/{$gp['profile_id']}");
                return $status;
            });

        return $replies->toArray();
    }

    public function storeComment(Request $request)
    {
        $this->validate($request, [
            'gid' => 'required|exists:groups,id',
            'sid' => 'required|exists:group_posts,id',
            'cid' => 'sometimes',
            'content' => 'required|string|min:1|max:1500'
        ]);

        $pid = $request->user()->profile_id;
        $gid = $request->input('gid');
        $sid = $request->input('sid');
        $cid = $request->input('cid');
        $limit = $request->input('limit', 3);
        $caption = e($request->input('content'));

        $group = Group::findOrFail($gid);

        abort_if(!$group->isMember($pid), 403, 'Not a member of group.');
        abort_if(!GroupService::canComment($gid, $pid), 422, 'You cannot interact with this content at this time');


        $parent = $cid == 1 ?
            GroupComment::findOrFail($sid) :
            GroupPost::whereGroupId($gid)->findOrFail($sid);
        // $autolink = Purify::clean(Autolink::create()->autolink($caption));
        // $autolink = str_replace('/discover/tags/', '/groups/' . $gid . '/topics/', $autolink);

        $status = new GroupComment;
        $status->group_id = $group->id;
        $status->profile_id = $pid;
        $status->status_id = $parent->id;
        $status->caption = Purify::clean($caption);
        $status->visibility = 'public';
        $status->is_nsfw = false;
        $status->local = true;
        $status->save();

        NewCommentPipeline::dispatch($parent, $status)->onQueue('groups');
        // todo: perform in job
        $parent->reply_count = $parent->reply_count ? $parent->reply_count + $parent->reply_count : 1;
        $parent->save();
        GroupPostService::del($parent->group_id, $parent->id);

        GroupService::log(
            $group->id,
            $pid,
            'group:comment:created',
            [
                'type' => 'group:post:comment',
                'status_id' => $status->id
            ],
            GroupPost::class,
            $status->id
        );

        //GroupCommentPipeline::dispatch($parent, $status, $gp);
        //NewStatusPipeline::dispatch($status, $gp);
        //GroupPostService::del($group->id, GroupService::sidToGid($group->id, $parent->id));

        // todo: perform in job
        $s = GroupCommentService::get($status->group_id, $status->id);

        $s['pf_type'] = 'text';
        $s['visibility'] = 'public';
        $s['url'] = $status->url();

        return $s;
    }

    public function storeCommentPhoto(Request $request)
    {
        $this->validate($request, [
            'gid' => 'required|exists:groups,id',
            'sid' => 'required|exists:group_posts,id',
            'photo' => 'required|image'
        ]);

        $pid = $request->user()->profile_id;
        $gid = $request->input('gid');
        $sid = $request->input('sid');
        $limit = $request->input('limit', 3);
        $caption = $request->input('content');

        $group = Group::findOrFail($gid);

        abort_if(!$group->isMember($pid), 403, 'Not a member of group.');
        abort_if(!GroupService::canComment($gid, $pid), 422, 'You cannot interact with this content at this time');
        $parent = GroupPost::whereGroupId($gid)->findOrFail($sid);

        $status = new GroupComment;
        $status->status_id = $parent->id;
        $status->group_id = $group->id;
        $status->profile_id = $pid;
        $status->caption = Purify::clean($caption);
        $status->visibility = 'draft';
        $status->is_nsfw = false;
        $status->save();

        $photo = $request->file('photo');
        $storagePath = GroupMediaService::path($group->id, $pid, $status->id);
        $storagePath = 'public/g/' . $group->id . '/p/' . $parent->id;
        $path = $photo->storePublicly($storagePath);

        $media = new GroupMedia();
        $media->group_id = $group->id;
        $media->status_id = $status->id;
        $media->profile_id = $request->user()->profile_id;
        $media->media_path = $path;
        $media->size = $photo->getSize();
        $media->mime = $photo->getMimeType();
        $media->save();

        ImageResizePipeline::dispatchSync($media);
        ImageS3UploadPipeline::dispatchSync($media);

        // $gp = new GroupPost;
        // $gp->group_id = $group->id;
        // $gp->profile_id = $pid;
        // $gp->type = 'reply:photo';
        // $gp->status_id = $status->id;
        // $gp->in_reply_to_id = $parent->id;
        // $gp->save();

        // GroupService::log(
        //  $group->id,
        //  $pid,
        //  'group:comment:created',
        //  [
        //      'type' => $gp->type,
        //      'status_id' => $status->id
        //  ],
        //  GroupPost::class,
        //  $gp->id
        // );

        // todo: perform in job
        // $parent->reply_count = Status::whereInReplyToId($parent->id)->count();
        // $parent->save();
        // StatusService::del($parent->id);
        // GroupPostService::del($group->id, GroupService::sidToGid($group->id, $parent->id));

        // delay response while background job optimizes media
        // sleep(5);

        // todo: perform in job
        $s = GroupCommentService::get($status->group_id, $status->id);

        // $s['pf_type'] = 'text';
        // $s['visibility'] = 'public';
        // $s['url'] = $gp->url();

        return $s;
    }

    public function deleteComment(Request $request)
    {
        abort_if(!$request->user(), 403);

        $this->validate($request, [
          'id'  => 'required|integer|min:1',
          'gid' => 'required|integer|min:1'
        ]);

        $pid = $request->user()->profile_id;
        $gid = $request->input('gid');
        $group = Group::findOrFail($gid);
        abort_if(!$group->isMember($pid), 403, 'Not a member of group.');

        $gp = GroupComment::whereGroupId($group->id)->findOrFail($request->input('id'));
        abort_if($gp->profile_id != $pid && $group->profile_id != $pid, 403);

        $parent = GroupPost::find($gp->status_id);
        abort_if(!$parent, 422, 'Invalid parent');

        DeleteCommentPipeline::dispatch($parent, $gp)->onQueue('groups');
        GroupService::log(
            $group->id,
            $pid,
            'group:status:deleted',
            [
                'type' => $gp->type,
                'status_id' => $gp->id,
            ],
            GroupComment::class,
            $gp->id
        );
        $gp->delete();

        if($request->wantsJson()) {
            return response()->json(['Status successfully deleted.']);
        } else {
            return redirect('/groups/feed');
        }
    }

    public function likePost(Request $request)
    {
        $this->validate($request, [
            'gid' => 'required',
            'sid' => 'required'
        ]);

        $pid = $request->user()->profile_id;
        $gid = $request->input('gid');
        $sid = $request->input('sid');

        $group = GroupService::get($gid);
        abort_if(!$group || $gid != $group['id'], 422, 'Invalid group');
        abort_if(!GroupService::canLike($gid, $pid), 422, 'You cannot interact with this content at this time');
        abort_if(!GroupService::isMember($gid, $pid), 403, 'Not a member of group');
        $gp = GroupCommentService::get($gid, $sid);
        abort_if(!$gp, 422, 'Invalid status');
        $count = $gp['favourites_count'] ?? 0;

        $like = GroupLike::firstOrCreate([
            'group_id' => $gid,
            'profile_id' => $pid,
            'comment_id' => $sid,
        ]);

        if($like->wasRecentlyCreated) {
            // update parent post like count
            $parent = GroupComment::find($sid);
            abort_if(!$parent || $parent->group_id != $gid, 422, 'Invalid status');
            $parent->likes_count = $parent->likes_count + 1;
            $parent->save();
            GroupsLikeService::add($pid, $sid);
            // invalidate cache
            GroupCommentService::del($gid, $sid);
            $count++;
            GroupService::log(
                $gid,
                $pid,
                'group:like',
                null,
                GroupLike::class,
                $like->id
            );
        }

        $response = ['code' => 200, 'msg' => 'Like saved', 'count' => $count];

        return $response;
    }

    public function unlikePost(Request $request)
    {
        $this->validate($request, [
            'gid' => 'required',
            'sid' => 'required'
        ]);

        $pid = $request->user()->profile_id;
        $gid = $request->input('gid');
        $sid = $request->input('sid');

        $group = GroupService::get($gid);
        abort_if(!$group || $gid != $group['id'], 422, 'Invalid group');
        abort_if(!GroupService::canLike($gid, $pid), 422, 'You cannot interact with this content at this time');
        abort_if(!GroupService::isMember($gid, $pid), 403, 'Not a member of group');
        $gp = GroupCommentService::get($gid, $sid);
        abort_if(!$gp, 422, 'Invalid status');
        $count = $gp['favourites_count'] ?? 0;

        $like = GroupLike::where([
            'group_id' => $gid,
            'profile_id' => $pid,
            'comment_id' => $sid,
        ])->first();

        if($like) {
            $like->delete();
            $parent = GroupComment::find($sid);
            abort_if(!$parent || $parent->group_id != $gid, 422, 'Invalid status');
            $parent->likes_count = $parent->likes_count - 1;
            $parent->save();
            GroupsLikeService::remove($pid, $sid);
            // invalidate cache
            GroupCommentService::del($gid, $sid);
            $count--;
        }

        $response = ['code' => 200, 'msg' => 'Unliked post', 'count' => $count];

        return $response;
    }
}
