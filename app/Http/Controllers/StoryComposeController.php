<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Media;
use App\Profile;
use App\Report;
use App\DirectMessage;
use App\Notification;
use App\Status;
use App\Story;
use App\StoryView;
use App\Models\Poll;
use App\Models\PollVote;
use App\Services\ProfileService;
use App\Services\StoryService;
use Cache, Storage;
use Image as Intervention;
use App\Services\FollowerService;
use App\Services\MediaPathService;
use FFMpeg;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Format\Video\X264;
use App\Jobs\StoryPipeline\StoryReactionDeliver;
use App\Jobs\StoryPipeline\StoryReplyDeliver;
use App\Jobs\StoryPipeline\StoryFanout;
use App\Jobs\StoryPipeline\StoryDelete;
use ImageOptimizer;

class StoryComposeController extends Controller
{
    public function apiV1Add(Request $request)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$this->validate($request, [
			'file' => function() {
				return [
					'required',
					'mimetypes:image/jpeg,image/png,video/mp4',
					'max:' . config_cache('pixelfed.max_photo_size'),
				];
			},
		]);

		$user = $request->user();

		$count = Story::whereProfileId($user->profile_id)
			->whereActive(true)
			->where('expires_at', '>', now())
			->count();

		if($count >= Story::MAX_PER_DAY) {
			abort(418, 'You have reached your limit for new Stories today.');
		}

		$photo = $request->file('file');
		$path = $this->storePhoto($photo, $user);

		$story = new Story();
		$story->duration = 3;
		$story->profile_id = $user->profile_id;
		$story->type = Str::endsWith($photo->getMimeType(), 'mp4') ? 'video' :'photo';
		$story->mime = $photo->getMimeType();
		$story->path = $path;
		$story->local = true;
		$story->size = $photo->getSize();
		$story->bearcap_token = str_random(64);
		$story->expires_at = now()->addMinutes(1440);
		$story->save();

		$url = $story->path;

		$res = [
			'code' => 200,
			'msg'  => 'Successfully added',
			'media_id' => (string) $story->id,
			'media_url' => url(Storage::url($url)) . '?v=' . time(),
			'media_type' => $story->type
		];

		if($story->type === 'video') {
			$video = FFMpeg::open($path);
			$duration = $video->getDurationInSeconds();
			$res['media_duration'] = $duration;
			if($duration > 500) {
				Storage::delete($story->path);
				$story->delete();
				return response()->json([
					'message' => 'Video duration cannot exceed 60 seconds'
				], 422);
			}
		}

		return $res;
	}

	protected function storePhoto($photo, $user)
	{
		$mimes = explode(',', config_cache('pixelfed.media_types'));
		if(in_array($photo->getMimeType(), [
			'image/jpeg',
			'image/png',
			'video/mp4'
		]) == false) {
			abort(400, 'Invalid media type');
			return;
		}

		$storagePath = MediaPathService::story($user->profile);
		$path = $photo->storeAs($storagePath, Str::random(random_int(2, 12)) . '_' . Str::random(random_int(32, 35)) . '_' . Str::random(random_int(1, 14)) . '.' . $photo->extension());
		if(in_array($photo->getMimeType(), ['image/jpeg','image/png'])) {
			$fpath = storage_path('app/' . $path);
			$img = Intervention::make($fpath);
			$img->orientate();
			$img->save($fpath, config_cache('pixelfed.image_quality'));
			$img->destroy();
		}
		return $path;
	}

	public function cropPhoto(Request $request)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$this->validate($request, [
			'media_id' => 'required|integer|min:1',
			'width' => 'required',
			'height' => 'required',
			'x' => 'required',
			'y' => 'required'
		]);

		$user = $request->user();
		$id = $request->input('media_id');
		$width = round($request->input('width'));
		$height = round($request->input('height'));
		$x = round($request->input('x'));
		$y = round($request->input('y'));

		$story = Story::whereProfileId($user->profile_id)->findOrFail($id);

		$path = storage_path('app/' . $story->path);

		if(!is_file($path)) {
			abort(400, 'Invalid or missing media.');
		}

		if($story->type === 'photo') {
			$img = Intervention::make($path);
			$img->crop($width, $height, $x, $y);
			$img->resize(1080, 1920, function ($constraint) {
				$constraint->aspectRatio();
			});
			$img->save($path, config_cache('pixelfed.image_quality'));
		}

		return [
			'code' => 200,
			'msg'  => 'Successfully cropped',
		];
	}

	public function publishStory(Request $request)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$this->validate($request, [
			'media_id' => 'required',
			'duration' => 'required|integer|min:3|max:120',
			'can_reply' => 'required|boolean',
			'can_react' => 'required|boolean'
		]);

		$id = $request->input('media_id');
		$user = $request->user();
		$story = Story::whereProfileId($user->profile_id)
			->findOrFail($id);

		$story->active = true;
		$story->duration = $request->input('duration', 10);
		$story->can_reply = $request->input('can_reply');
		$story->can_react = $request->input('can_react');
		$story->save();

		StoryService::delLatest($story->profile_id);
		StoryFanout::dispatch($story)->onQueue('story');
		StoryService::addRotateQueue($story->id);

		return [
			'code' => 200,
			'msg'  => 'Successfully published',
		];
	}

	public function apiV1Delete(Request $request, $id)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$user = $request->user();

		$story = Story::whereProfileId($user->profile_id)
			->findOrFail($id);
		$story->active = false;
		$story->save();

		StoryDelete::dispatch($story)->onQueue('story');

		return [
			'code' => 200,
			'msg'  => 'Successfully deleted'
		];
	}

	public function compose(Request $request)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		return view('stories.compose');
	}

	public function createPoll(Request $request)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);
		abort_if(!config_cache('instance.polls.enabled'), 404);

		return $request->all();
	}

	public function publishStoryPoll(Request $request)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$this->validate($request, [
			'question' => 'required|string|min:6|max:140',
			'options' => 'required|array|min:2|max:4',
			'can_reply' => 'required|boolean',
			'can_react' => 'required|boolean'
		]);

		$pid = $request->user()->profile_id;

		$count = Story::whereProfileId($pid)
			->whereActive(true)
			->where('expires_at', '>', now())
			->count();

		if($count >= Story::MAX_PER_DAY) {
			abort(418, 'You have reached your limit for new Stories today.');
		}

		$story = new Story;
		$story->type = 'poll';
		$story->story = json_encode([
			'question' => $request->input('question'),
			'options' => $request->input('options')
		]);
		$story->public = false;
		$story->local = true;
		$story->profile_id = $pid;
		$story->expires_at = now()->addMinutes(1440);
		$story->duration = 30;
		$story->can_reply = false;
		$story->can_react = false;
		$story->save();

		$poll = new Poll;
		$poll->story_id = $story->id;
		$poll->profile_id = $pid;
		$poll->poll_options = $request->input('options');
		$poll->expires_at = $story->expires_at;
		$poll->cached_tallies = collect($poll->poll_options)->map(function($o) {
			return 0;
		})->toArray();
		$poll->save();

		$story->active = true;
		$story->save();

		StoryService::delLatest($story->profile_id);

		return [
			'code' => 200,
			'msg'  => 'Successfully published',
		];
	}

	public function storyPollVote(Request $request)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$this->validate($request, [
			'sid' => 'required',
			'ci' => 'required|integer|min:0|max:3'
		]);

		$pid = $request->user()->profile_id;
		$ci = $request->input('ci');
		$story = Story::findOrFail($request->input('sid'));
		abort_if(!FollowerService::follows($pid, $story->profile_id), 403);
		$poll = Poll::whereStoryId($story->id)->firstOrFail();

		$vote = new PollVote;
		$vote->profile_id = $pid;
		$vote->poll_id = $poll->id;
		$vote->story_id = $story->id;
		$vote->status_id = null;
		$vote->choice = $ci;
		$vote->save();

		$poll->votes_count = $poll->votes_count + 1;
    	$poll->cached_tallies = collect($poll->getTallies())->map(function($tally, $key) use($ci) {
    		return $ci == $key ? $tally + 1 : $tally;
    	})->toArray();
    	$poll->save();

		return 200;
	}

	public function storeReport(Request $request)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$this->validate($request, [
            'type'  => 'required|alpha_dash',
            'id'    => 'required|integer|min:1',
        ]);

        $pid = $request->user()->profile_id;
        $sid = $request->input('id');
        $type = $request->input('type');

        $types = [
            // original 3
            'spam',
            'sensitive',
            'abusive',

            // new
            'underage',
            'copyright',
            'impersonation',
            'scam',
            'terrorism'
        ];

        abort_if(!in_array($type, $types), 422, 'Invalid story report type');

        $story = Story::findOrFail($sid);

        abort_if($story->profile_id == $pid, 422, 'Cannot report your own story');
        abort_if(!FollowerService::follows($pid, $story->profile_id), 422, 'Cannot report a story from an account you do not follow');

        if( Report::whereProfileId($pid)
        	->whereObjectType('App\Story')
        	->whereObjectId($story->id)
        	->exists()
        ) {
        	return response()->json(['error' => [
        		'code' => 409,
        		'message' => 'Cannot report the same story again'
        	]], 409);
        }

		$report = new Report;
        $report->profile_id = $pid;
        $report->user_id = $request->user()->id;
        $report->object_id = $story->id;
        $report->object_type = 'App\Story';
        $report->reported_profile_id = $story->profile_id;
        $report->type = $type;
        $report->message = null;
        $report->save();

        return [200];
	}

	public function react(Request $request)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);
		$this->validate($request, [
			'sid' => 'required',
			'reaction' => 'required|string'
		]);
		$pid = $request->user()->profile_id;
		$text = $request->input('reaction');

		$story = Story::findOrFail($request->input('sid'));

		abort_if(!$story->can_react, 422);
		abort_if(StoryService::reactCounter($story->id, $pid) >= 5, 422, 'You have already reacted to this story');

		$status = new Status;
		$status->profile_id = $pid;
		$status->type = 'story:reaction';
		$status->caption = $text;
		$status->rendered = $text;
		$status->scope = 'direct';
		$status->visibility = 'direct';
		$status->in_reply_to_profile_id = $story->profile_id;
		$status->entities = json_encode([
			'story_id' => $story->id,
			'reaction' => $text
		]);
		$status->save();

		$dm = new DirectMessage;
		$dm->to_id = $story->profile_id;
		$dm->from_id = $pid;
		$dm->type = 'story:react';
		$dm->status_id = $status->id;
		$dm->meta = json_encode([
			'story_username' => $story->profile->username,
			'story_actor_username' => $request->user()->username,
			'story_id' => $story->id,
			'story_media_url' => url(Storage::url($story->path)),
			'reaction' => $text
		]);
		$dm->save();

		if($story->local) {
			// generate notification
			$n = new Notification;
			$n->profile_id = $dm->to_id;
			$n->actor_id = $dm->from_id;
			$n->item_id = $dm->id;
			$n->item_type = 'App\DirectMessage';
			$n->action = 'story:react';
			$n->message = "{$request->user()->username} reacted to your story";
			$n->rendered = "{$request->user()->username} reacted to your story";
			$n->save();
		} else {
			StoryReactionDeliver::dispatch($story, $status)->onQueue('story');
		}

		StoryService::reactIncrement($story->id, $pid);

		return 200;
	}

	public function comment(Request $request)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);
		$this->validate($request, [
			'sid' => 'required',
			'caption' => 'required|string'
		]);
		$pid = $request->user()->profile_id;
		$text = $request->input('caption');

		$story = Story::findOrFail($request->input('sid'));

		abort_if(!$story->can_reply, 422);

		$status = new Status;
		$status->type = 'story:reply';
		$status->profile_id = $pid;
		$status->caption = $text;
		$status->rendered = $text;
		$status->scope = 'direct';
		$status->visibility = 'direct';
		$status->in_reply_to_profile_id = $story->profile_id;
		$status->entities = json_encode([
			'story_id' => $story->id
		]);
		$status->save();

		$dm = new DirectMessage;
		$dm->to_id = $story->profile_id;
		$dm->from_id = $pid;
		$dm->type = 'story:comment';
		$dm->status_id = $status->id;
		$dm->meta = json_encode([
			'story_username' => $story->profile->username,
			'story_actor_username' => $request->user()->username,
			'story_id' => $story->id,
			'story_media_url' => url(Storage::url($story->path)),
			'caption' => $text
		]);
		$dm->save();

		if($story->local) {
			// generate notification
			$n = new Notification;
			$n->profile_id = $dm->to_id;
			$n->actor_id = $dm->from_id;
			$n->item_id = $dm->id;
			$n->item_type = 'App\DirectMessage';
			$n->action = 'story:comment';
			$n->message = "{$request->user()->username} commented on story";
			$n->rendered = "{$request->user()->username} commented on story";
			$n->save();
		} else {
			StoryReplyDeliver::dispatch($story, $status)->onQueue('story');
		}

		return 200;
	}
}
