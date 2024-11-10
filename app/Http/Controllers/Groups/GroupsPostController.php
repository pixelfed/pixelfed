<?php

namespace App\Http\Controllers\Groups;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AccountService;
use App\Services\GroupService;
use App\Services\Groups\GroupFeedService;
use App\Services\Groups\GroupPostService;
use App\Services\Groups\GroupMediaService;
use App\Services\Groups\GroupsLikeService;
use App\Follower;
use App\Profile;
use App\Models\Group;
use App\Models\GroupHashtag;
use App\Models\GroupPost;
use App\Models\GroupLike;
use App\Models\GroupMember;
use App\Models\GroupInvitation;
use App\Models\GroupMedia;
use App\Jobs\GroupsPipeline\ImageResizePipeline;
use App\Jobs\GroupsPipeline\ImageS3UploadPipeline;
use App\Jobs\GroupsPipeline\NewPostPipeline;

class GroupsPostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function storePost(Request $request)
    {
        $this->validate($request, [
            'group_id' => 'required|exists:groups,id',
            'caption' => 'sometimes|string|max:'.config_cache('pixelfed.max_caption_length', 500),
            'pollOptions' => 'sometimes|array|min:1|max:4'
        ]);

        $group = Group::findOrFail($request->input('group_id'));
        $pid = $request->user()->profile_id;
        $caption = $request->input('caption');
        $type = $request->input('type', 'text');

        abort_if(!GroupService::canPost($group->id, $pid), 422, 'You cannot create new posts at this time');

        if($type == 'text') {
            abort_if(strlen(e($caption)) == 0, 403);
        }

        $gp = new GroupPost;
        $gp->group_id = $group->id;
        $gp->profile_id = $pid;
        $gp->caption = e($caption);
        $gp->type = $type;
        $gp->visibility = 'draft';
        $gp->save();

        $status = $gp;

        NewPostPipeline::dispatchSync($gp);

        // NewStatusPipeline::dispatch($status, $gp);

        if($type == 'poll') {
            // Polls not supported yet
            // $poll = new Poll;
            // $poll->status_id = $status->id;
            // $poll->profile_id = $status->profile_id;
            // $poll->poll_options = $request->input('pollOptions');
            // $poll->expires_at = now()->addMinutes($request->input('expiry'));
            // $poll->cached_tallies = collect($poll->poll_options)->map(function($o) {
            //     return 0;
            // })->toArray();
            // $poll->save();
            // sleep(5);
        }
        if($type == 'photo') {
            $photo = $request->file('photo');
            $storagePath = GroupMediaService::path($group->id, $pid, $status->id);
            // $storagePath = 'public/g/' . $group->id . '/p/' . $status->id;
            $path = $photo->storePublicly($storagePath);
            // $hash = \hash_file('sha256', $photo);

            $media = new GroupMedia();
            $media->group_id = $group->id;
            $media->status_id = $status->id;
            $media->profile_id = $request->user()->profile_id;
            $media->media_path = $path;
            $media->size = $photo->getSize();
            $media->mime = $photo->getMimeType();
            $media->save();

            // Bus::chain([
            //     new ImageResizePipeline($media),
            //     new ImageS3UploadPipeline($media),
            // ])->dispatch($media);

            ImageResizePipeline::dispatchSync($media);
            ImageS3UploadPipeline::dispatchSync($media);
            // ImageOptimize::dispatch($media);
            // delay response while background job optimizes media
            // sleep(5);
        }
        if($type == 'video') {
            $video = $request->file('video');
            $storagePath = 'public/g/' . $group->id . '/p/' . $status->id;
            $path = $video->storePublicly($storagePath);
            $hash = \hash_file('sha256', $video);

            $media = new Media();
            $media->status_id = $status->id;
            $media->profile_id = $request->user()->profile_id;
            $media->user_id = $request->user()->id;
            $media->media_path = $path;
            $media->original_sha256 = $hash;
            $media->size = $video->getSize();
            $media->mime = $video->getMimeType();
            $media->save();

            VideoThumbnail::dispatch($media);
            sleep(15);
        }

        GroupService::log(
            $group->id,
            $pid,
            'group:status:created',
            [
                'type' => $gp->type,
                'status_id' => $status->id
            ],
            GroupPost::class,
            $gp->id
        );

        $s = GroupPostService::get($status->group_id, $status->id);
        GroupFeedService::add($group->id, $gp->id);
        Cache::forget('groups:self:feed:' . $pid);

        $s['pf_type'] = $type;
        $s['visibility'] = 'public';
        $s['url'] = $gp->url();

        if($type == 'poll') {
            $s['poll'] = PollService::get($status->id);
        }

        $group->last_active_at = now();
        $group->save();

        return $s;
    }

    public function deletePost(Request $request)
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

        $gp = GroupPost::whereGroupId($status->group_id)->findOrFail($request->input('id'));
        abort_if($gp->profile_id != $pid && $group->profile_id != $pid, 403);
        $cached = GroupPostService::get($status->group_id, $status->id);

        if($cached) {
            $cached = collect($cached)->filter(function($r, $k) {
                return in_array($k, [
                    'id',
                    'sensitive',
                    'pf_type',
                    'media_attachments',
                    'content_text',
                    'created_at'
                ]);
            });
        }

        GroupService::log(
            $status->group_id,
            $request->user()->profile_id,
            'group:status:deleted',
            [
                'type' => $gp->type,
                'status_id' => $status->id,
                'original' => $cached
            ],
            GroupPost::class,
            $gp->id
        );

        $user = $request->user();

        // if($status->profile_id != $user->profile->id &&
        //  $user->is_admin == true &&
        //  $status->uri == null
        // ) {
        //  $media = $status->media;

        //  $ai = new AccountInterstitial;
        //  $ai->user_id = $status->profile->user_id;
        //  $ai->type = 'post.removed';
        //  $ai->view = 'account.moderation.post.removed';
        //  $ai->item_type = 'App\Status';
        //  $ai->item_id = $status->id;
        //  $ai->has_media = (bool) $media->count();
        //  $ai->blurhash = $media->count() ? $media->first()->blurhash : null;
        //  $ai->meta = json_encode([
        //      'caption' => $status->caption,
        //      'created_at' => $status->created_at,
        //      'type' => $status->type,
        //      'url' => $status->url(),
        //      'is_nsfw' => $status->is_nsfw,
        //      'scope' => $status->scope,
        //      'reblog' => $status->reblog_of_id,
        //      'likes_count' => $status->likes_count,
        //      'reblogs_count' => $status->reblogs_count,
        //  ]);
        //  $ai->save();

        //  $u = $status->profile->user;
        //  $u->has_interstitial = true;
        //  $u->save();
        // }

        if($status->in_reply_to_id) {
            $parent = GroupPost::find($status->in_reply_to_id);
            if($parent) {
                $parent->reply_count = GroupPost::whereInReplyToId($parent->id)->count();
                $parent->save();
                GroupPostService::del($group->id, GroupService::sidToGid($group->id, $parent->id));
            }
        }

        GroupPostService::del($group->id, $gp->id);
        GroupFeedService::del($group->id, $gp->id);
        if ($status->profile_id == $user->profile->id || $user->is_admin == true) {
            // Cache::forget('profile:status_count:'.$status->profile_id);
            StatusDelete::dispatch($status);
        }

        if($request->wantsJson()) {
            return response()->json(['Status successfully deleted.']);
        } else {
            return redirect($user->url());
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
        abort_if(!$group, 422, 'Invalid group');
        abort_if(!GroupService::canLike($gid, $pid), 422, 'You cannot interact with this content at this time');
        abort_if(!GroupService::isMember($gid, $pid), 403, 'Not a member of group');
        $gp = GroupPostService::get($gid, $sid);
        abort_if(!$gp, 422, 'Invalid status');
        $count = $gp['favourites_count'] ?? 0;

        $like = GroupLike::firstOrCreate([
            'group_id' => $gid,
            'profile_id' => $pid,
            'status_id' => $sid,
        ]);

        if($like->wasRecentlyCreated) {
            // update parent post like count
            $parent = GroupPost::whereGroupId($gid)->find($sid);
            abort_if(!$parent, 422, 'Invalid status');
            $parent->likes_count = $parent->likes_count + 1;
            $parent->save();
            GroupsLikeService::add($pid, $sid);
            // invalidate cache
            GroupPostService::del($gid, $sid);
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
        // if (GroupLike::whereGroupId($gid)->whereStatusId($sid)->whereProfileId($pid)->exists()) {
        //     $like = GroupLike::whereProfileId($pid)->whereStatusId($sid)->firstOrFail();
        //     // UnlikePipeline::dispatch($like);
        //     $count = $gp->likes_count - 1;
        //     $action = 'group:unlike';
        // } else {
        //     $count = $gp->likes_count;
        //     $like = GroupLike::firstOrCreate([
        //         'group_id' => $gid,
        //         'profile_id' => $pid,
        //         'status_id' => $sid
        //     ]);
        //     if($like->wasRecentlyCreated == true) {
        //         $count++;
        //         $gp->likes_count = $count;
        //         $like->save();
        //         $gp->save();
        //         // LikePipeline::dispatch($like);
        //         $action = 'group:like';
        //     }
        // }


        // Cache::forget('status:'.$status->id.':likedby:userid:'.$request->user()->id);
        // StatusService::del($status->id);

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
        abort_if(!$group, 422, 'Invalid group');
        abort_if(!GroupService::canLike($gid, $pid), 422, 'You cannot interact with this content at this time');
        abort_if(!GroupService::isMember($gid, $pid), 403, 'Not a member of group');
        $gp = GroupPostService::get($gid, $sid);
        abort_if(!$gp, 422, 'Invalid status');
        $count = $gp['favourites_count'] ?? 0;

        $like = GroupLike::where([
            'group_id' => $gid,
            'profile_id' => $pid,
            'status_id' => $sid,
        ])->first();

        if($like) {
            $like->delete();
            $parent = GroupPost::whereGroupId($gid)->find($sid);
            abort_if(!$parent, 422, 'Invalid status');
            $parent->likes_count = $parent->likes_count - 1;
            $parent->save();
            GroupsLikeService::remove($pid, $sid);
            // invalidate cache
            GroupPostService::del($gid, $sid);
            $count--;
        }

        $response = ['code' => 200, 'msg' => 'Unliked post', 'count' => $count];

        return $response;
    }

    public function getGroupMedia(Request $request)
    {
        $this->validate($request, [
            'gid' => 'required',
            'type' => 'required|in:photo,video'
        ]);

        abort_if(!$request->user(), 404);

        $pid = $request->user()->profile_id;
        $gid = $request->input('gid');
        $type = $request->input('type');
        $group = Group::findOrFail($gid);

        abort_if(!$group->isMember($pid), 403, 'Not a member of group.');

        $media = GroupPost::whereGroupId($gid)
            ->whereType($type)
            ->latest()
            ->simplePaginate(20)
            ->map(function($gp) use($pid) {
                $status = GroupPostService::get($gp['group_id'], $gp['id']);
                if(!$status) {
                    return false;
                }
                $status['favourited'] = (bool) GroupsLikeService::liked($pid, $gp['id']);
                $status['favourites_count'] = GroupsLikeService::count($gp['id']);
                $status['pf_type'] = $gp['type'];
                $status['visibility'] = 'public';
                $status['url'] = $gp->url();

                // if($gp['type'] == 'poll') {
                //     $status['poll'] = PollService::get($status['id']);
                // }

                return $status;
            })->filter(function($status) {
                return $status;
            });

        return response()->json($media->toArray(), 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    }
}
