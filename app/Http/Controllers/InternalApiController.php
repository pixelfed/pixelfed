<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{
	AccountInterstitial,
	Bookmark,
	DirectMessage,
	DiscoverCategory,
	Hashtag,
	Follower,
	Like,
	Media,
	MediaTag,
	Notification,
	Profile,
	StatusHashtag,
	Status,
	UserFilter,
};
use Auth,Cache;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use League\Fractal;
use App\Transformer\Api\{
	AccountTransformer,
	StatusTransformer,
	// StatusMediaContainerTransformer,
};
use App\Util\Media\Filter;
use App\Jobs\StatusPipeline\NewStatusPipeline;
use App\Jobs\ModPipeline\HandleSpammerPipeline;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Services\MediaTagService;
use App\Services\ModLogService;
use App\Services\PublicTimelineService;
use App\Services\SnowflakeService;
use App\Services\StatusService;

class InternalApiController extends Controller
{
	protected $fractal;

	public function __construct()
	{
		$this->middleware('auth');
		$this->fractal = new Fractal\Manager();
		$this->fractal->setSerializer(new ArraySerializer());
	}

	// deprecated v2 compose api
	public function compose(Request $request)
	{
		return redirect('/');
	}

	// deprecated
	public function discover(Request $request)
	{
		return;
	}

	public function discoverPosts(Request $request)
	{
		$profile = Auth::user()->profile;
		$pid = $profile->id;
		$following = Cache::remember('feature:discover:following:'.$pid, now()->addMinutes(15), function() use ($pid) {
			return Follower::whereProfileId($pid)->pluck('following_id')->toArray();
		});
		$filters = Cache::remember("user:filter:list:$pid", now()->addMinutes(15), function() use($pid) {
			$private = Profile::whereIsPrivate(true)
				->orWhere('unlisted', true)
				->orWhere('status', '!=', null)
				->pluck('id')
				->toArray();
			$filters = UserFilter::whereUserId($pid)
				->whereFilterableType('App\Profile')
				->whereIn('filter_type', ['mute', 'block'])
				->pluck('filterable_id')
				->toArray();
			return array_merge($private, $filters);
		});
		$following = array_merge($following, $filters);

		$sql = config('database.default') !== 'pgsql';
		$min_id = SnowflakeService::byDate(now()->subMonths(3));
		$posts = Status::select(
				'id',
				'is_nsfw',
				'profile_id',
				'type',
				'uri',
			  )
			  ->whereNull('uri')
			  ->whereIn('type', ['photo','photo:album', 'video'])
			  ->whereIsNsfw(false)
			  ->whereVisibility('public')
			  ->whereNotIn('profile_id', $following)
			  ->where('id', '>', $min_id)
			  ->inRandomOrder()
			  ->take(39)
			  ->pluck('id');

		$res = [
			'posts' => $posts->map(function($post) {
				return StatusService::get($post);
			})
		];
		return response()->json($res);
	}

	public function directMessage(Request $request, $profileId, $threadId)
	{
		$profile = Auth::user()->profile;

		if($profileId != $profile->id) {
			abort(403);
		}

		$msg = DirectMessage::whereToId($profile->id)
			->orWhere('from_id',$profile->id)
			->findOrFail($threadId);

		$thread = DirectMessage::with('status')->whereIn('to_id', [$profile->id, $msg->from_id])
			->whereIn('from_id', [$profile->id,$msg->from_id])
			->orderBy('created_at', 'asc')
			->paginate(30);

		return response()->json(compact('msg', 'profile', 'thread'), 200, [], JSON_PRETTY_PRINT);
	}

	public function statusReplies(Request $request, int $id)
	{
		$this->validate($request, [
			'limit' => 'nullable|int|min:1|max:6'
		]);
		$parent = Status::whereScope('public')->findOrFail($id);
		$limit = $request->input('limit') ?? 3;
		$children = Status::whereInReplyToId($parent->id)
			->orderBy('created_at', 'desc')
			->take($limit)
			->get();
		$resource = new Fractal\Resource\Collection($children, new StatusTransformer());
		$res = $this->fractal->createData($resource)->toArray();

		return response()->json($res);
	}

	public function stories(Request $request)
	{

	}

	public function discoverCategories(Request $request)
	{
		$categories = DiscoverCategory::whereActive(true)->orderBy('order')->take(10)->get();
		$res = $categories->map(function($item) {
			return [
				'name' => $item->name,
				'url' => $item->url(),
				'thumb' => $item->thumb()
			];
		});
		return response()->json($res);
	}

	public function modAction(Request $request)
	{
		abort_unless(Auth::user()->is_admin, 400);
		$this->validate($request, [
			'action' => [
				'required',
				'string',
				Rule::in([
					'addcw',
					'remcw',
					'unlist',
					'spammer'
				])
			],
			'item_id' => 'required|integer|min:1',
			'item_type' => [
				'required',
				'string',
				Rule::in(['profile', 'status'])
			]
		]);

		$action = $request->input('action');
		$item_id = $request->input('item_id');
		$item_type = $request->input('item_type');

		switch($action) {
			case 'addcw':
				$status = Status::findOrFail($item_id);
				$status->is_nsfw = true;
				$status->save();
				ModLogService::boot()
					->user(Auth::user())
					->objectUid($status->profile->user_id)
					->objectId($status->id)
					->objectType('App\Status::class')
					->action('admin.status.moderate')
					->metadata([
						'action' => 'cw',
						'message' => 'Success!'
					])
					->accessLevel('admin')
					->save();


				if($status->uri == null) {
					$media = $status->media;
					$ai = new AccountInterstitial;
					$ai->user_id = $status->profile->user_id;
					$ai->type = 'post.cw';
					$ai->view = 'account.moderation.post.cw';
					$ai->item_type = 'App\Status';
					$ai->item_id = $status->id;
					$ai->has_media = (bool) $media->count();
					$ai->blurhash = $media->count() ? $media->first()->blurhash : null;
					$ai->meta = json_encode([
						'caption' => $status->caption,
						'created_at' => $status->created_at,
						'type' => $status->type,
						'url' => $status->url(),
						'is_nsfw' => $status->is_nsfw,
						'scope' => $status->scope,
						'reblog' => $status->reblog_of_id,
						'likes_count' => $status->likes_count,
						'reblogs_count' => $status->reblogs_count,
					]);
					$ai->save();

					$u = $status->profile->user;
					$u->has_interstitial = true;
					$u->save();
				}
			break;

			case 'remcw':
				$status = Status::findOrFail($item_id);
				$status->is_nsfw = false;
				$status->save();
				ModLogService::boot()
					->user(Auth::user())
					->objectUid($status->profile->user_id)
					->objectId($status->id)
					->objectType('App\Status::class')
					->action('admin.status.moderate')
					->metadata([
						'action' => 'remove_cw',
						'message' => 'Success!'
					])
					->accessLevel('admin')
					->save();
				if($status->uri == null) {
					$ai = AccountInterstitial::whereUserId($status->profile->user_id)
						->whereType('post.cw')
						->whereItemId($status->id)
						->whereItemType('App\Status')
						->first();
					$ai->delete();
				}
			break;

			case 'unlist':
				$status = Status::whereScope('public')->findOrFail($item_id);
				$status->scope = $status->visibility = 'unlisted';
				$status->save();
				PublicTimelineService::del($status->id);
				ModLogService::boot()
					->user(Auth::user())
					->objectUid($status->profile->user_id)
					->objectId($status->id)
					->objectType('App\Status::class')
					->action('admin.status.moderate')
					->metadata([
						'action' => 'unlist',
						'message' => 'Success!'
					])
					->accessLevel('admin')
					->save();

				if($status->uri == null) {
					$media = $status->media;
					$ai = new AccountInterstitial;
					$ai->user_id = $status->profile->user_id;
					$ai->type = 'post.unlist';
					$ai->view = 'account.moderation.post.unlist';
					$ai->item_type = 'App\Status';
					$ai->item_id = $status->id;
					$ai->has_media = (bool) $media->count();
					$ai->blurhash = $media->count() ? $media->first()->blurhash : null;
					$ai->meta = json_encode([
						'caption' => $status->caption,
						'created_at' => $status->created_at,
						'type' => $status->type,
						'url' => $status->url(),
						'is_nsfw' => $status->is_nsfw,
						'scope' => $status->scope,
						'reblog' => $status->reblog_of_id,
						'likes_count' => $status->likes_count,
						'reblogs_count' => $status->reblogs_count,
					]);
					$ai->save();

					$u = $status->profile->user;
					$u->has_interstitial = true;
					$u->save();
				}
			break;

			case 'spammer':
				$status = Status::findOrFail($item_id);
				HandleSpammerPipeline::dispatch($status->profile);
				ModLogService::boot()
					->user(Auth::user())
					->objectUid($status->profile->user_id)
					->objectId($status->id)
					->objectType('App\User::class')
					->action('admin.status.moderate')
					->metadata([
						'action' => 'spammer',
						'message' => 'Success!'
					])
					->accessLevel('admin')
					->save();
			break;
		}

		Cache::forget('_api:statuses:recent_9:' . $status->profile_id);
		Cache::forget('profile:embed:' . $status->profile_id);
		StatusService::del($status->id);

		return ['msg' => 200];
	}

	public function composePost(Request $request)
	{
		abort(400, 'Endpoint deprecated');
	}

	public function bookmarks(Request $request)
	{
		$res = Bookmark::whereProfileId($request->user()->profile_id)
			->orderByDesc('created_at')
			->simplePaginate(10)
			->map(function($bookmark) {
				$status = StatusService::get($bookmark->status_id);
				$status['bookmarked_at'] = $bookmark->created_at->format('c');
				return $status;
			})
			->filter(function($bookmark) {
				return isset($bookmark['id']);
			})
			->values();

		return response()->json($res);
	}

	public function accountStatuses(Request $request, $id)
	{
		$this->validate($request, [
			'only_media' => 'nullable',
			'pinned' => 'nullable',
			'exclude_replies' => 'nullable',
			'max_id' => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
			'since_id' => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
			'min_id' => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
			'limit' => 'nullable|integer|min:1|max:24'
		]);

		$profile = Profile::whereNull('status')->findOrFail($id);

		$limit = $request->limit ?? 9;
		$max_id = $request->max_id;
		$min_id = $request->min_id;
		$scope = $request->only_media == true ?
			['photo', 'photo:album', 'video', 'video:album'] :
			['photo', 'photo:album', 'video', 'video:album', 'share', 'reply'];

		if($profile->is_private) {
			if(!Auth::check()) {
				return response()->json([]);
			}
			$pid = Auth::user()->profile->id;
			$following = Cache::remember('profile:following:'.$pid, now()->addMinutes(1440), function() use($pid) {
				$following = Follower::whereProfileId($pid)->pluck('following_id');
				return $following->push($pid)->toArray();
			});
			$visibility = true == in_array($profile->id, $following) ? ['public', 'unlisted', 'private'] : [];
		} else {
			if(Auth::check()) {
				$pid = Auth::user()->profile->id;
				$following = Cache::remember('profile:following:'.$pid, now()->addMinutes(1440), function() use($pid) {
					$following = Follower::whereProfileId($pid)->pluck('following_id');
					return $following->push($pid)->toArray();
				});
				$visibility = true == in_array($profile->id, $following) ? ['public', 'unlisted', 'private'] : ['public', 'unlisted'];
			} else {
				$visibility = ['public', 'unlisted'];
			}
		}

		$dir = $min_id ? '>' : '<';
		$id = $min_id ?? $max_id;
		$timeline = Status::select(
			'id',
			'uri',
			'caption',
			'rendered',
			'profile_id',
			'type',
			'in_reply_to_id',
			'reblog_of_id',
			'is_nsfw',
			'likes_count',
			'reblogs_count',
			'scope',
			'local',
			'created_at',
			'updated_at'
		  )->whereProfileId($profile->id)
		  ->whereIn('type', $scope)
		  ->where('id', $dir, $id)
		  ->whereIn('visibility', $visibility)
		  ->latest()
		  ->limit($limit)
		  ->get();

		$resource = new Fractal\Resource\Collection($timeline, new StatusTransformer());
		$res = $this->fractal->createData($resource)->toArray();

		return response()->json($res);
	}

	public function remoteProfile(Request $request, $id)
	{
		$profile = Profile::whereNull('status')
			->whereNotNull('domain')
			->findOrFail($id);
		$user = Auth::user();

		return view('profile.remote', compact('profile', 'user'));
	}

	public function remoteStatus(Request $request, $profileId, $statusId)
	{
		$user = Profile::whereNull('status')
			->whereNotNull('domain')
			->findOrFail($profileId);

		$status = Status::whereProfileId($user->id)
						->whereNull('reblog_of_id')
						->whereIn('visibility', ['public', 'unlisted'])
						->findOrFail($statusId);
		$template = $status->in_reply_to_id ? 'status.reply' : 'status.remote';
		return view($template, compact('user', 'status'));
	}

	public function requestEmailVerification(Request $request)
	{
		$pid = $request->user()->profile_id;
		$exists = Redis::sismember('email:manual', $pid);
		return view('account.email.request_verification', compact('exists'));
	}

	public function requestEmailVerificationStore(Request $request)
	{
		$pid = $request->user()->profile_id;
		Redis::sadd('email:manual', $pid);
		return redirect('/i/verify-email')->with(['status' => 'Successfully sent manual verification request!']);
	}
}
