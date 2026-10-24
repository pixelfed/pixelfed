<?php

namespace App\Http\Controllers;

use App\Jobs\StoryPipeline\StoryDelete;
use App\Jobs\StoryPipeline\StoryFanout;
use App\Jobs\StoryPipeline\StoryReactionDeliver;
use App\Jobs\StoryPipeline\StoryReplyDeliver;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Report;
use App\Models\Status;
use App\Models\Story;
use App\Services\DirectMessageService;
use App\Services\FollowerService;
use App\Services\MediaPathService;
use App\Services\StoryIndexService;
use App\Services\StoryService;
use App\Services\UserFilterService;
use App\Services\UserRoleService;
use App\Util\Media\ImageDriverManager;
use FFMpeg;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
// GLITCH MODIFIED: webp stories
use Intervention\Image\Encoders\WebpEncoder;

class StoryComposeController extends Controller
{
    protected $imageManager;

    public function __construct()
    {
        $this->imageManager = ImageDriverManager::createImageManager();
    }

    public function apiV1Add(Request $request)
    {
        abort_if(! (bool) config_cache('instance.stories.enabled') || ! $request->user(), 404);

        $this->validate($request, [
            'file' => [
                'required',
                // GLITCH MODIFIED: stories accept all configured MEDIA_TYPES
                'mimetypes:'.config_cache('pixelfed.media_types'),
                'max:'.config_cache('pixelfed.max_photo_size'),
            ],
        ]);

        $user = $request->user();
        // GLITCH MODIFIED: translatable message
        abort_if($user->has_roles && ! UserRoleService::can('can-use-stories', $user->id), 403, __('Invalid permissions for this action'));
        $count = Story::whereProfileId($user->profile_id)
            ->whereActive(true)
            ->where('expires_at', '>', now())
            ->count();

        if ($count >= Story::MAX_PER_DAY) {
            // GLITCH MODIFIED: translatable message
            abort(418, __('You have reached your limit for new Stories today.'));
        }

        // GLITCH MODIFIED START: storePhoto returns [path, duration, mime]; media is transcoded so size/mime/type come from the stored file
        $path = null;
        $photo = $request->file('file');

        try {
            [$path, $duration, $mimeType] = $this->storePhoto($photo, $user);
        }
        catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        $disk = Storage::disk(config('filesystems.default'));

        $story = new Story;
        $story->duration = $duration;
        $story->profile_id = $user->profile_id;
        $story->mime = $mimeType;
        $story->type = Str::startsWith($mimeType, 'video') ? 'video' : 'photo';
        $story->path = $path;
        $story->local = true;
        $story->size = $disk->size($path);
        // GLITCH MODIFIED END
        $story->bearcap_token = Str::random(64);
        $story->expires_at = now()->addMinutes(1440);
        $story->save();

        $url = $story->path;

        $res = [
            'code' => 200,
            // GLITCH MODIFIED START: translatable message; local vs remote URL inline (video duration check moved into storePhoto)
            'msg' => __('Successfully added'),
            'media_id' => (string) $story->id,
            'media_url' => (config('filesystems.default') === 'local') ?
                url(Storage::url($url)).'?v='.time() :
                $disk->url($url).'?v='.time(),
            // GLITCH MODIFIED END
            'media_type' => $story->type,
        ];

        // GLITCH MODIFIED: duration now comes from storePhoto; StoryCompose.vue reads media_duration for videos
        if ($story->type === 'video') {
            $res['media_duration'] = $duration;
        }

        return $res;
    }

    // GLITCH MODIFIED START: rewritten: any MEDIA_TYPES, images re-encoded (webp/png kept, others to jpeg), video and animated images transcoded to webm, duration limits from instance.stories.duration.*
    protected function storePhoto($photo, $user)
    {
        $mimes = explode(',', config_cache('pixelfed.media_types'));
        if (in_array($photo->getMimeType(), $mimes) == false) {
            abort(400, __('Unauthorized media type'));

            return;
        }

        $disk = Storage::disk(config('filesystems.default'));
        $storagePath = MediaPathService::story($user->profile);
        $filename = Str::random(random_int(2, 12)).'_'.Str::random(random_int(32, 35)).'_'.Str::random(random_int(1, 14));
        $originalExtension = strtolower($photo->extension());
        $originalPath = $photo->storePubliclyAs($storagePath, $filename.'.'.$originalExtension);
        $encodedPath = null;
        $mimeType = null;
        $duration = config_cache('instance.stories.duration.preferred');

        try {
            $img = null;

            if (Str::startsWith($photo->getMimeType(), 'image')
                && ($img = $this->imageManager->decodeBinary($disk->get($originalPath)))
                && ! $img->isAnimated()) {
                $quality = config_cache('pixelfed.image_quality');

                switch ($originalExtension) {
                    case 'png':
                        $encoder = new PngEncoder;
                        $outputExtension = 'png';
                        $mimeType = 'image/png';
                        break;
                    case 'webp':
                        $encoder = new WebpEncoder($quality);
                        $outputExtension = 'webp';
                        $mimeType = 'image/webp';
                        break;
                    case 'jpeg':
                    case 'jpg':
                    case 'gif':
                    case 'avif':
                    case 'heic':
                    default:
                        $encoder = new JpegEncoder($quality);
                        $outputExtension = 'jpg';
                        $mimeType = 'image/jpeg';
                }

                $encoded = $img->encode($encoder);

                $encodedPath = $storagePath.'/'.$filename.'.'.$outputExtension;
                $disk->put($encodedPath, (string) $encoded);
                if ($outputExtension !== $originalExtension) $disk->delete($originalPath);
            }
            else {
                $video = FFMpeg::fromDisk(config('filesystems.default'))->open($originalPath);
                $duration = $video->getDurationInSeconds();
                $outputExtension = 'webm';
                $mimeType = 'video/webm';

                if ($duration > config_cache('instance.stories.duration.max')) {
                    throw new \Exception(__('Stories cannot exceed :time seconds', ['time' => config_cache('instance.stories.duration.max')]), 1);
                } else if ($duration < config_cache('instance.stories.duration.min')) {
                    throw new \Exception(__('Stories cannot be shorter than :time seconds', ['time' => config_cache('instance.stories.duration.min')]), 1);
                }

                $format = new \FFMpeg\Format\Video\WebM;
                
                // Transparency encoding with auto_alt_ref does not work with GIF https://github.com/PHP-FFMpeg/PHP-FFMpeg/issues/254
                if ($photo->extension() === "gif") {
                    $format = $format->setAdditionalParameters(["-auto-alt-ref", "0"]);
                }

                $encodedPath = $storagePath.'/'.$filename.'.'.$outputExtension;
                $video->export()->inFormat($format)->save($encodedPath);
                if ($outputExtension !== $originalExtension) $disk->delete($originalPath);
            }

        } catch (\Exception $e) {
            if ($disk->exists($originalPath)) {
                $disk->delete($originalPath);
            }
            if ($encodedPath && $disk->exists($encodedPath)) {
                $disk->delete($encodedPath);
            }

            throw new \Exception($e->getMessage(), 1, $e);
        }

        return [$encodedPath, $duration, $mimeType];
    // GLITCH MODIFIED END
    }

    public function cropPhoto(Request $request): array
    {
        abort_if(! (bool) config_cache('instance.stories.enabled') || ! $request->user(), 404);

        $this->validate($request, [
            'media_id' => 'required|integer|min:1',
            'width' => 'required',
            'height' => 'required',
            'x' => 'required',
            'y' => 'required',
        ]);

        $user = $request->user();
        $id = $request->input('media_id');
        $width = round($request->input('width'));
        $height = round($request->input('height'));
        $x = round($request->input('x'));
        $y = round($request->input('y'));

        $story = Story::whereProfileId($user->profile_id)->findOrFail($id);

        $localFs = config('filesystems.default') === 'local';

        if ($localFs) {
            $path = storage_path('app/'.$story->path);

            if (! is_file($path)) {
                abort(400, 'Invalid or missing media.');
            }
        } else {
            $disk = Storage::disk(config('filesystems.default'));

            if (! $disk->exists($story->path)) {
                abort(400, 'Invalid or missing media.');
            }
        }

        if ($story->type === 'photo') {
            $quality = config_cache('pixelfed.image_quality');

            if ($localFs) {
                $path = storage_path('app/'.$story->path);
                $extension = pathinfo($path, PATHINFO_EXTENSION);

                $img = $this->imageManager->decodePath($path);
                $img = $img->crop($width, $height, $x, $y);
                $img = $img->cover(1080, 1920);

                if (in_array(strtolower($extension), ['jpg', 'jpeg'])) {
                    $encoder = new JpegEncoder($quality);
                } else {
                    $encoder = new PngEncoder;
                }

                $encoded = $img->encode($encoder);
                file_put_contents($path, (string) $encoded);
            } else {
                $disk = Storage::disk(config('filesystems.default'));
                $extension = pathinfo($story->path, PATHINFO_EXTENSION);

                $fileContent = $disk->get($story->path);

                $img = $this->imageManager->decodeBinary($fileContent);
                $img = $img->crop($width, $height, $x, $y);
                $img = $img->cover(1080, 1920);

                if (in_array(strtolower($extension), ['jpg', 'jpeg'])) {
                    $encoder = new JpegEncoder($quality);
                } else {
                    $encoder = new PngEncoder;
                }

                $encoded = $img->encode($encoder);

                $disk->put($story->path, (string) $encoded);
            }
        }

        return [
            'code' => 200,
            'msg' => 'Successfully cropped',
        ];
    }

    public function publishStory(Request $request): array
    {
        abort_if(! (bool) config_cache('instance.stories.enabled') || ! $request->user(), 404);

        $this->validate($request, [
            'media_id' => 'required',
            'duration' => 'required|integer|min:3|max:120',
            'can_reply' => 'required|boolean',
            'can_react' => 'required|boolean',
        ]);

        $id = $request->input('media_id');
        $user = $request->user();
        abort_if($user->has_roles && ! UserRoleService::can('can-use-stories', $user->id), 403, 'Invalid permissions for this action');
        $story = Story::whereProfileId($user->profile_id)
            ->findOrFail($id);

        $story->active = true;
        $story->duration = $request->input('duration', 10);
        $story->can_reply = $request->input('can_reply');
        $story->can_react = $request->input('can_react');
        $story->save();

        $index = app(StoryIndexService::class);
        $index->indexStory($story);

        StoryService::delLatest($story->profile_id);
        StoryFanout::dispatch($story)->onQueue('story');
        StoryService::addRotateQueue($story->id);

        return [
            'code' => 200,
            'msg' => 'Successfully published',
        ];
    }

    public function apiV1Delete(Request $request, $id): array
    {
        abort_if(! (bool) config_cache('instance.stories.enabled') || ! $request->user(), 404);

        $user = $request->user();

        $story = Story::whereProfileId($user->profile_id)
            ->findOrFail($id);
        $story->active = false;
        $story->save();

        $index = app(StoryIndexService::class);
        $index->removeStory($story->id, $story->profile_id);

        StoryDelete::dispatch($story)->onQueue('story');

        return [
            'code' => 200,
            'msg' => 'Successfully deleted',
        ];
    }

    public function compose(Request $request): View
    {
        abort_if(! (bool) config_cache('instance.stories.enabled') || ! $request->user(), 404);
        $user = $request->user();
        abort_if($user->has_roles && ! UserRoleService::can('can-use-stories', $user->id), 403, 'Invalid permissions for this action');

        return view('stories.compose');
    }

    public function publishStoryPoll(Request $request): array
    {
        abort_if(! (bool) config_cache('instance.stories.enabled') || ! $request->user(), 404);

        $this->validate($request, [
            'question' => 'required|string|min:6|max:140',
            'options' => 'required|array|min:2|max:4',
            'can_reply' => 'required|boolean',
            'can_react' => 'required|boolean',
        ]);

        $user = $request->user();
        abort_if($user->has_roles && ! UserRoleService::can('can-use-stories', $user->id), 403, 'Invalid permissions for this action');
        $pid = $request->user()->profile_id;

        $count = Story::whereProfileId($pid)
            ->whereActive(true)
            ->where('expires_at', '>', now())
            ->count();

        if ($count >= Story::MAX_PER_DAY) {
            abort(418, 'You have reached your limit for new Stories today.');
        }

        $story = new Story;
        $story->type = 'poll';
        $story->story = json_encode([
            'question' => $request->input('question'),
            'options' => $request->input('options'),
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
        $poll->cached_tallies = collect($poll->poll_options)->map(function ($o) {
            return 0;
        })->toArray();
        $poll->save();

        $story->active = true;
        $story->save();

        StoryService::delLatest($story->profile_id);

        return [
            'code' => 200,
            'msg' => 'Successfully published',
        ];
    }

    public function storyPollVote(Request $request): int
    {
        abort_if(! (bool) config_cache('instance.stories.enabled') || ! $request->user(), 404);

        $this->validate($request, [
            'sid' => 'required',
            'ci' => 'required|integer|min:0|max:3',
        ]);

        $pid = $request->user()->profile_id;
        $ci = $request->input('ci');
        $story = Story::findOrFail($request->input('sid'));
        abort_if(now()->gt($story->expires_at), 404);
        abort_if($story->profile_id == $pid, 422, 'Cannot vote on your own story');
        abort_if(! FollowerService::follows($pid, $story->profile_id), 422, 'Cannot vote on a story from an account you do not follow');
        abort_if(in_array($pid, UserFilterService::blocks($story->profile_id)), 403);
        $poll = Poll::whereStoryId($story->id)->firstOrFail();

        $vote = new PollVote;
        $vote->profile_id = $pid;
        $vote->poll_id = $poll->id;
        $vote->story_id = $story->id;
        $vote->status_id = null;
        $vote->choice = $ci;
        $vote->save();

        $poll->votes_count = $poll->votes_count + 1;
        $poll->cached_tallies = collect($poll->getTallies())->map(function ($tally, $key) use ($ci) {
            return $ci == $key ? $tally + 1 : $tally;
        })->toArray();
        $poll->save();

        return 200;
    }

    public function storeReport(Request $request)
    {
        abort_if(! (bool) config_cache('instance.stories.enabled') || ! $request->user(), 404);

        $this->validate($request, [
            'type' => 'required|alpha_dash',
            'id' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        abort_if($user->has_roles && ! UserRoleService::can('can-use-stories', $user->id), 403, 'Invalid permissions for this action');

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
            'terrorism',
        ];

        abort_if(! in_array($type, $types), 422, 'Invalid story report type');

        $story = Story::findOrFail($sid);
        abort_if(now()->gt($story->expires_at), 404);
        abort_if($story->profile_id == $pid, 422, 'Cannot report your own story');
        abort_if(! FollowerService::follows($pid, $story->profile_id), 422, 'Cannot report a story from an account you do not follow');
        abort_if(in_array($pid, UserFilterService::blocks($story->profile_id)), 403);

        if (Report::whereProfileId($pid)
            ->whereObjectType(Story::class)
            ->whereObjectId($story->id)
            ->exists()
        ) {
            return response()->json(['error' => [
                'code' => 409,
                'message' => 'Cannot report the same story again',
            ]], 409);
        }

        $report = new Report;
        $report->profile_id = $pid;
        $report->user_id = $request->user()->id;
        $report->object_id = $story->id;
        $report->object_type = Story::class;
        $report->reported_profile_id = $story->profile_id;
        $report->type = $type;
        $report->message = null;
        $report->save();

        return [200];
    }

    public function react(Request $request): int
    {
        abort_if(! (bool) config_cache('instance.stories.enabled') || ! $request->user(), 404);
        $this->validate($request, [
            'sid' => 'required',
            'reaction' => 'required|string',
        ]);
        $pid = $request->user()->profile_id;
        $text = $request->input('reaction');
        $user = $request->user();
        abort_if($user->has_roles && ! UserRoleService::can('can-use-stories', $user->id), 403, 'Invalid permissions for this action');
        $story = Story::findOrFail($request->input('sid'));
        abort_if(now()->gt($story->expires_at), 404);
        abort_if(
            $story->profile_id !== $pid && ! FollowerService::follows($pid, $story->profile_id),
            403
        );
        abort_if(in_array($pid, UserFilterService::blocks($story->profile_id)), 403);

        abort_if(! $story->can_react, 422);
        abort_if(StoryService::reactCounter($story->id, $pid) >= 5, 422, 'You have already reacted to this story');

        $status = new Status;
        $status->profile_id = $pid;
        $status->type = 'story:reaction';
        $status->caption = $text;
        $status->scope = 'direct';
        $status->visibility = 'direct';
        $status->in_reply_to_profile_id = $story->profile_id;
        $status->entities = json_encode([
            'story_id' => $story->id,
            'reaction' => $text,
        ]);
        $status->save();

        $localFs = config('filesystems.default') === 'local';
        $mediaUrl = $localFs
            ? url(Storage::url($story->path))
            : Storage::disk(config('filesystems.default'))->url($story->path);

        // Shows up in the conversation with the story author, who is
        // notified when they are on this server
        app(DirectMessageService::class)->storeStoryMessage(
            $request->user()->profile,
            $story->profile,
            'story:react',
            $text,
            [
                'story_username' => $story->profile->username,
                'story_actor_username' => $request->user()->username,
                'story_id' => $story->id,
                'story_media_url' => $mediaUrl,
                'reaction' => $text,
            ],
            $status->id
        );

        if (! $story->local) {
            StoryReactionDeliver::dispatch($story, $status)->onQueue('story');
        }

        StoryService::reactIncrement($story->id, $pid);

        return 200;
    }

    public function comment(Request $request): int
    {
        abort_if(! (bool) config_cache('instance.stories.enabled') || ! $request->user(), 404);
        $this->validate($request, [
            'sid' => 'required',
            'caption' => 'required|string',
        ]);
        $pid = $request->user()->profile_id;
        $text = $request->input('caption');
        $user = $request->user();
        abort_if($user->has_roles && ! UserRoleService::can('can-use-stories', $user->id), 403, 'Invalid permissions for this action');
        $story = Story::findOrFail($request->input('sid'));
        abort_if(now()->gt($story->expires_at), 404);
        abort_if(
            $story->profile_id !== $pid && ! FollowerService::follows($pid, $story->profile_id),
            403
        );
        abort_if(in_array($pid, UserFilterService::blocks($story->profile_id)), 403);

        abort_if(! $story->can_reply, 422);

        $status = new Status;
        $status->type = 'story:reply';
        $status->profile_id = $pid;
        $status->caption = $text;
        $status->scope = 'direct';
        $status->visibility = 'direct';
        $status->in_reply_to_profile_id = $story->profile_id;
        $status->entities = json_encode([
            'story_id' => $story->id,
        ]);
        $status->save();

        $localFs = config('filesystems.default') === 'local';
        $mediaUrl = $localFs
            ? url(Storage::url($story->path))
            : Storage::disk(config('filesystems.default'))->url($story->path);

        // Shows up in the conversation with the story author, who is
        // notified when they are on this server
        app(DirectMessageService::class)->storeStoryMessage(
            $request->user()->profile,
            $story->profile,
            'story:comment',
            $text,
            [
                'story_username' => $story->profile->username,
                'story_actor_username' => $request->user()->username,
                'story_id' => $story->id,
                'story_media_url' => $mediaUrl,
                'caption' => $text,
            ],
            $status->id
        );

        if (! $story->local) {
            StoryReplyDeliver::dispatch($story, $status)->onQueue('story');
        }

        return 200;
    }
}
