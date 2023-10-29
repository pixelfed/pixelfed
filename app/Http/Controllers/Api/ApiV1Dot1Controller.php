<?php

namespace App\Http\Controllers\Api;

use Cache;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use App\AccountLog;
use App\EmailVerification;
use App\Follower;
use App\Place;
use App\Status;
use App\Report;
use App\Profile;
use App\StatusArchived;
use App\User;
use App\UserSetting;
use App\Services\AccountService;
use App\Services\StatusService;
use App\Services\ProfileStatusService;
use App\Services\LikeService;
use App\Services\ReblogService;
use App\Services\PublicTimelineService;
use App\Services\NetworkTimelineService;
use App\Util\Lexer\RestrictedNames;
use App\Services\BouncerService;
use App\Services\EmailService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Jenssegers\Agent\Agent;
use Mail;
use App\Mail\PasswordChange;
use App\Mail\ConfirmAppEmail;
use App\Http\Resources\StatusStateless;
use App\Jobs\StatusPipeline\StatusDelete;
use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Jobs\ReportPipeline\ReportNotifyAdminViaEmail;
use Illuminate\Support\Facades\RateLimiter;

class ApiV1Dot1Controller extends Controller
{
	protected $fractal;

	public function __construct()
	{
		$this->fractal = new Fractal\Manager();
		$this->fractal->setSerializer(new ArraySerializer());
	}

	public function json($res, $code = 200, $headers = [])
	{
		return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
	}

	public function error($msg, $code = 400, $extra = [], $headers = [])
	{
		$res = [
			"msg" => $msg,
			"code" => $code
		];
		return response()->json(array_merge($res, $extra), $code, $headers, JSON_UNESCAPED_SLASHES);
	}

	public function report(Request $request)
	{
		$user = $request->user();

		abort_if(!$user, 403);
		abort_if($user->status != null, 403);

		if(config('pixelfed.bouncer.cloud_ips.ban_signups')) {
			abort_if(BouncerService::checkIp($request->ip()), 404);
		}

		$report_type = $request->input('report_type');
		$object_id = $request->input('object_id');
		$object_type = $request->input('object_type');

		$types = [
			'spam',
			'sensitive',
			'abusive',
			'underage',
			'violence',
			'copyright',
			'impersonation',
			'scam',
			'terrorism'
		];

		if (!$report_type || !$object_id || !$object_type) {
			return $this->error("Invalid or missing parameters", 400, ["error_code" => "ERROR_INVALID_PARAMS"]);
		}

		if (!in_array($report_type, $types)) {
			return $this->error("Invalid report type", 400, ["error_code" => "ERROR_TYPE_INVALID"]);
		}

		if ($object_type === "user" && $object_id == $user->profile_id) {
			return $this->error("Cannot self report", 400, ["error_code" => "ERROR_NO_SELF_REPORTS"]);
		}

		$rpid = null;

		switch ($object_type) {
			case 'post':
				$object = Status::find($object_id);
				if (!$object) {
					return $this->error("Invalid object id", 400, ["error_code" => "ERROR_INVALID_OBJECT_ID"]);
				}
				$object_type = 'App\Status';
				$exists = Report::whereUserId($user->id)
					->whereObjectId($object->id)
					->whereObjectType('App\Status')
					->count();

				$rpid = $object->profile_id;
			break;

			case 'user':
				$object = Profile::find($object_id);
				if (!$object) {
					return $this->error("Invalid object id", 400, ["error_code" => "ERROR_INVALID_OBJECT_ID"]);
				}
				$object_type = 'App\Profile';
				$exists = Report::whereUserId($user->id)
					->whereObjectId($object->id)
					->whereObjectType('App\Profile')
					->count();
				$rpid = $object->id;
			break;

			default:
				return $this->error("Invalid report type", 400, ["error_code" => "ERROR_REPORT_OBJECT_TYPE_INVALID"]);
			break;
	  }

		if ($exists !== 0) {
			return $this->error("Duplicate report", 400, ["error_code" => "ERROR_REPORT_DUPLICATE"]);
		}

		if ($object->profile_id == $user->profile_id) {
			return $this->error("Cannot self report", 400, ["error_code" => "ERROR_NO_SELF_REPORTS"]);
		}

		$report = new Report;
		$report->profile_id = $user->profile_id;
		$report->user_id = $user->id;
		$report->object_id = $object->id;
		$report->object_type = $object_type;
		$report->reported_profile_id = $rpid;
		$report->type = $report_type;
		$report->save();

		if(config('instance.reports.email.enabled')) {
			ReportNotifyAdminViaEmail::dispatch($report)->onQueue('default');
		}

		$res = [
			"msg" => "Successfully sent report",
			"code" => 200
		];
		return $this->json($res);
	}

	/**
	 * DELETE /api/v1.1/accounts/avatar
	 *
	 * @return \App\Transformer\Api\AccountTransformer
	 */
	public function deleteAvatar(Request $request)
	{
		$user = $request->user();

		abort_if(!$user, 403);
		abort_if($user->status != null, 403);

		if(config('pixelfed.bouncer.cloud_ips.ban_signups')) {
			abort_if(BouncerService::checkIp($request->ip()), 404);
		}

		$avatar = $user->profile->avatar;

		if( $avatar->media_path == 'public/avatars/default.png' ||
			$avatar->media_path == 'public/avatars/default.jpg'
		) {
			return AccountService::get($user->profile_id);
		}

		if(is_file(storage_path('app/' . $avatar->media_path))) {
			@unlink(storage_path('app/' . $avatar->media_path));
		}

		$avatar->media_path = 'public/avatars/default.jpg';
		$avatar->change_count = $avatar->change_count + 1;
		$avatar->save();

		Cache::forget('avatar:' . $user->profile_id);
		Cache::forget("avatar:{$user->profile_id}");
		Cache::forget('user:account:id:'.$user->id);
		AccountService::del($user->profile_id);

		return AccountService::get($user->profile_id);
	}

	/**
	 * GET /api/v1.1/accounts/{id}/posts
	 *
	 * @return \App\Transformer\Api\StatusTransformer
	 */
	public function accountPosts(Request $request, $id)
	{
		$user = $request->user();

		abort_if(!$user, 403);
		abort_if($user->status != null, 403);

		if(config('pixelfed.bouncer.cloud_ips.ban_signups')) {
			abort_if(BouncerService::checkIp($request->ip()), 404);
		}

		$account = AccountService::get($id);

		if(!$account || $account['username'] !== $request->input('username')) {
			return $this->json([]);
		}

		$posts = ProfileStatusService::get($id);

		if(!$posts) {
			return $this->json([]);
		}

		$res = collect($posts)
			->map(function($id) {
				return StatusService::get($id);
			})
			->filter(function($post) {
				return $post && isset($post['account']);
			})
			->toArray();

		return $this->json($res);
	}

	/**
	 * POST /api/v1.1/accounts/change-password
	 *
	 * @return \App\Transformer\Api\AccountTransformer
	 */
	public function accountChangePassword(Request $request)
	{
		$user = $request->user();
		abort_if(!$user, 403);
		abort_if($user->status != null, 403);
		if(config('pixelfed.bouncer.cloud_ips.ban_signups')) {
			abort_if(BouncerService::checkIp($request->ip()), 404);
		}

		$this->validate($request, [
			'current_password' => 'bail|required|current_password',
			'new_password' => 'required|min:' . config('pixelfed.min_password_length', 8),
			'confirm_password' => 'required|same:new_password'
		],[
			'current_password' => 'The password you entered is incorrect'
		]);

		$user->password = bcrypt($request->input('new_password'));
		$user->save();

		$log = new AccountLog;
		$log->user_id = $user->id;
		$log->item_id = $user->id;
		$log->item_type = 'App\User';
		$log->action = 'account.edit.password';
		$log->message = 'Password changed';
		$log->link = null;
		$log->ip_address = $request->ip();
		$log->user_agent = $request->userAgent();
		$log->save();

		Mail::to($request->user())->send(new PasswordChange($user));

		return $this->json(AccountService::get($user->profile_id));
	}

	/**
	 * GET /api/v1.1/accounts/login-activity
	 *
	 * @return array
	 */
	public function accountLoginActivity(Request $request)
	{
		$user = $request->user();
		abort_if(!$user, 403);
		abort_if($user->status != null, 403);
		if(config('pixelfed.bouncer.cloud_ips.ban_signups')) {
			abort_if(BouncerService::checkIp($request->ip()), 404);
		}
		$agent = new Agent();
		$currentIp = $request->ip();

		$activity = AccountLog::whereUserId($user->id)
			->whereAction('auth.login')
			->orderBy('created_at', 'desc')
			->groupBy('ip_address')
			->limit(10)
			->get()
			->map(function($item) use($agent, $currentIp) {
				$agent->setUserAgent($item->user_agent);
				return [
					'id' => $item->id,
					'action' => $item->action,
					'ip' => $item->ip_address,
					'ip_current' => $item->ip_address === $currentIp,
					'is_mobile' => $agent->isMobile(),
					'device' => $agent->device(),
					'browser' => $agent->browser(),
					'platform' => $agent->platform(),
					'created_at' => $item->created_at->format('c')
				];
			});

		return $this->json($activity);
	}

	/**
	 * GET /api/v1.1/accounts/two-factor
	 *
	 * @return array
	 */
	public function accountTwoFactor(Request $request)
	{
		$user = $request->user();
		abort_if(!$user, 403);
		abort_if($user->status != null, 403);

		if(config('pixelfed.bouncer.cloud_ips.ban_signups')) {
			abort_if(BouncerService::checkIp($request->ip()), 404);
		}

		$res = [
			'active' => (bool) $user->{'2fa_enabled'},
			'setup_at' => $user->{'2fa_setup_at'}
		];
		return $this->json($res);
	}

	/**
	 * GET /api/v1.1/accounts/emails-from-pixelfed
	 *
	 * @return array
	 */
	public function accountEmailsFromPixelfed(Request $request)
	{
		$user = $request->user();
		abort_if(!$user, 403);
		abort_if($user->status != null, 403);
		if(config('pixelfed.bouncer.cloud_ips.ban_signups')) {
			abort_if(BouncerService::checkIp($request->ip()), 404);
		}
		$from = config('mail.from.address');

		$emailVerifications = EmailVerification::whereUserId($user->id)
			->orderByDesc('id')
			->where('created_at', '>', now()->subDays(14))
			->limit(10)
			->get()
			->map(function($mail) use($user, $from) {
				return [
					'type' => 'Email Verification',
					'subject' => 'Confirm Email',
					'to_address' => $user->email,
					'from_address' => $from,
					'created_at' => str_replace('@', 'at', $mail->created_at->format('M j, Y @ g:i:s A'))
				];
			})
			->toArray();

		$passwordResets = DB::table('password_resets')
			->whereEmail($user->email)
			->where('created_at', '>', now()->subDays(14))
			->orderByDesc('created_at')
			->limit(10)
			->get()
			->map(function($mail) use($user, $from) {
				return [
					'type' => 'Password Reset',
					'subject' => 'Reset Password Notification',
					'to_address' => $user->email,
					'from_address' => $from,
					'created_at' => str_replace('@', 'at', now()->parse($mail->created_at)->format('M j, Y @ g:i:s A'))
				];
			})
			->toArray();

		$passwordChanges = AccountLog::whereUserId($user->id)
			->whereAction('account.edit.password')
			->where('created_at', '>', now()->subDays(14))
			->orderByDesc('created_at')
			->limit(10)
			->get()
			->map(function($mail) use($user, $from) {
				return [
					'type' => 'Password Change',
					'subject' => 'Password Change',
					'to_address' => $user->email,
					'from_address' => $from,
					'created_at' => str_replace('@', 'at', now()->parse($mail->created_at)->format('M j, Y @ g:i:s A'))
				];
			})
			->toArray();

		$res = collect([])
			->merge($emailVerifications)
			->merge($passwordResets)
			->merge($passwordChanges)
			->sortByDesc('created_at')
			->values();

		return $this->json($res);
	}

	/**
	 * GET /api/v1.1/accounts/apps-and-applications
	 *
	 * @return array
	 */
	public function accountApps(Request $request)
	{
		$user = $request->user();
		abort_if(!$user, 403);
		abort_if($user->status != null, 403);

		if(config('pixelfed.bouncer.cloud_ips.ban_signups')) {
			abort_if(BouncerService::checkIp($request->ip()), 404);
		}

		$res = $user->tokens->sortByDesc('created_at')->take(10)->map(function($token, $key) use($request) {
			return [
				'id' => $token->id,
				'current_session' => $request->user()->token()->id == $token->id,
				'name' => $token->client->name,
				'scopes' => $token->scopes,
				'revoked' => $token->revoked,
				'created_at' => str_replace('@', 'at', now()->parse($token->created_at)->format('M j, Y @ g:i:s A')),
				'expires_at' => str_replace('@', 'at', now()->parse($token->expires_at)->format('M j, Y @ g:i:s A'))
			];
		});

		return $this->json($res);
	}

	public function inAppRegistrationPreFlightCheck(Request $request)
	{
		return [
			'open' => (bool) config_cache('pixelfed.open_registration'),
			'iara' => config('pixelfed.allow_app_registration')
		];
	}

	public function inAppRegistration(Request $request)
	{
		abort_if($request->user(), 404);
		abort_unless(config_cache('pixelfed.open_registration'), 404);
		abort_unless(config('pixelfed.allow_app_registration'), 404);
		abort_unless($request->hasHeader('X-PIXELFED-APP'), 403);
		if(config('pixelfed.bouncer.cloud_ips.ban_signups')) {
			abort_if(BouncerService::checkIp($request->ip()), 404);
		}

		$rl = RateLimiter::attempt('pf:apiv1.1:iar:'.$request->ip(), config('pixelfed.app_registration_rate_limit_attempts', 3), function(){}, config('pixelfed.app_registration_rate_limit_decay', 1800));
		abort_if(!$rl, 400, 'Too many requests');

		$this->validate($request, [
			'email' => [
				'required',
				'string',
				'email',
				'max:255',
				'unique:users',
				function ($attribute, $value, $fail) {
					$banned = EmailService::isBanned($value);
					if($banned) {
						return $fail('Email is invalid.');
					}
				},
			],
			'username' => [
				'required',
				'min:2',
				'max:15',
				'unique:users',
				function ($attribute, $value, $fail) {
					$dash = substr_count($value, '-');
					$underscore = substr_count($value, '_');
					$period = substr_count($value, '.');

					if(ends_with($value, ['.php', '.js', '.css'])) {
						return $fail('Username is invalid.');
					}

					if(($dash + $underscore + $period) > 1) {
						return $fail('Username is invalid. Can only contain one dash (-), period (.) or underscore (_).');
					}

					if (!ctype_alnum($value[0])) {
						return $fail('Username is invalid. Must start with a letter or number.');
					}

					if (!ctype_alnum($value[strlen($value) - 1])) {
						return $fail('Username is invalid. Must end with a letter or number.');
					}

					$val = str_replace(['_', '.', '-'], '', $value);
					if(!ctype_alnum($val)) {
						return $fail('Username is invalid. Username must be alpha-numeric and may contain dashes (-), periods (.) and underscores (_).');
					}

					$restricted = RestrictedNames::get();
					if (in_array(strtolower($value), array_map('strtolower', $restricted))) {
						return $fail('Username cannot be used.');
					}
				},
			],
			'password' => 'required|string|min:8',
		]);

		$email = $request->input('email');
		$username = $request->input('username');
		$password = $request->input('password');

		if(config('database.default') == 'pgsql') {
			$username = strtolower($username);
			$email = strtolower($email);
		}

		$user = new User;
		$user->name = $username;
		$user->username = $username;
		$user->email = $email;
		$user->password = Hash::make($password);
		$user->register_source = 'app';
		$user->app_register_ip = $request->ip();
		$user->app_register_token = Str::random(40);
		$user->save();

		$rtoken = Str::random(64);

		$verify = new EmailVerification();
		$verify->user_id = $user->id;
		$verify->email = $user->email;
		$verify->user_token = $user->app_register_token;
		$verify->random_token = $rtoken;
		$verify->save();

		$params = http_build_query([
			'ut' => $user->app_register_token,
			'rt' => $rtoken,
			'ea' => base64_encode($user->email)
		]);
		$appUrl = url('/api/v1.1/auth/iarer?'. $params);

		Mail::to($user->email)->send(new ConfirmAppEmail($verify, $appUrl));

		return response()->json([
			'success' => true,
		]);
	}

	public function inAppRegistrationEmailRedirect(Request $request)
	{
		$this->validate($request, [
			'ut' => 'required',
			'rt' => 'required',
			'ea' => 'required'
		]);
		$ut = $request->input('ut');
		$rt = $request->input('rt');
		$ea = $request->input('ea');
		$params = http_build_query([
			'ut' => $ut,
			'rt' => $rt,
			'domain' => config('pixelfed.domain.app'),
			'ea' => $ea
		]);
		$url = 'pixelfed://confirm-account/'. $ut . '?' . $params;
		return redirect()->away($url);
	}

	public function inAppRegistrationConfirm(Request $request)
	{
		abort_if($request->user(), 404);
		abort_unless(config_cache('pixelfed.open_registration'), 404);
		abort_unless(config('pixelfed.allow_app_registration'), 404);
		abort_unless($request->hasHeader('X-PIXELFED-APP'), 403);
		if(config('pixelfed.bouncer.cloud_ips.ban_signups')) {
			abort_if(BouncerService::checkIp($request->ip()), 404);
		}

		$rl = RateLimiter::attempt('pf:apiv1.1:iarc:'.$request->ip(), config('pixelfed.app_registration_confirm_rate_limit_attempts', 20), function(){}, config('pixelfed.app_registration_confirm_rate_limit_decay', 1800));
		abort_if(!$rl, 429, 'Too many requests');

		$this->validate($request, [
			'user_token' => 'required',
			'random_token' => 'required',
			'email' => 'required'
		]);

		$verify = EmailVerification::whereEmail($request->input('email'))
			->whereUserToken($request->input('user_token'))
			->whereRandomToken($request->input('random_token'))
			->first();

		if(!$verify) {
			return response()->json(['error' => 'Invalid tokens'], 403);
		}

		if($verify->created_at->lt(now()->subHours(24))) {
			$verify->delete();
			return response()->json(['error' => 'Invalid tokens'], 403);
		}

		$user = User::findOrFail($verify->user_id);
		$user->email_verified_at = now();
		$user->last_active_at = now();
		$user->save();

		$token = $user->createToken('Pixelfed');

		return response()->json([
			'access_token' => $token->accessToken
		]);
	}

	public function archive(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		if(config('pixelfed.bouncer.cloud_ips.ban_signups')) {
			abort_if(BouncerService::checkIp($request->ip()), 404);
		}

		$status = Status::whereNull('in_reply_to_id')
			->whereNull('reblog_of_id')
			->whereProfileId($request->user()->profile_id)
			->findOrFail($id);

		if($status->scope === 'archived') {
			return [200];
		}

		$archive = new StatusArchived;
		$archive->status_id = $status->id;
		$archive->profile_id = $status->profile_id;
		$archive->original_scope = $status->scope;
		$archive->save();

		$status->scope = 'archived';
		$status->visibility = 'draft';
		$status->save();
		StatusService::del($status->id, true);
		AccountService::syncPostCount($status->profile_id);

		return [200];
	}

	public function unarchive(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		if(config('pixelfed.bouncer.cloud_ips.ban_signups')) {
			abort_if(BouncerService::checkIp($request->ip()), 404);
		}

		$status = Status::whereNull('in_reply_to_id')
			->whereNull('reblog_of_id')
			->whereProfileId($request->user()->profile_id)
			->findOrFail($id);

		if($status->scope !== 'archived') {
			return [200];
		}

		$archive = StatusArchived::whereStatusId($status->id)
			->whereProfileId($status->profile_id)
			->firstOrFail();

		$status->scope = $archive->original_scope;
		$status->visibility = $archive->original_scope;
		$status->save();
		$archive->delete();
		StatusService::del($status->id, true);
		AccountService::syncPostCount($status->profile_id);

		return [200];
	}

	public function archivedPosts(Request $request)
	{
		abort_if(!$request->user(), 403);

		if(config('pixelfed.bouncer.cloud_ips.ban_signups')) {
			abort_if(BouncerService::checkIp($request->ip()), 404);
		}

		$statuses = Status::whereProfileId($request->user()->profile_id)
			->whereScope('archived')
			->orderByDesc('id')
			->cursorPaginate(10);

		return StatusStateless::collection($statuses);
	}

	public function placesById(Request $request, $id, $slug)
	{
		abort_if(!$request->user(), 403);

		if(config('pixelfed.bouncer.cloud_ips.ban_signups')) {
			abort_if(BouncerService::checkIp($request->ip()), 404);
		}

		$place = Place::whereSlug($slug)->findOrFail($id);

		$posts = Cache::remember('pf-api:v1.1:places-by-id:' . $place->id, 3600, function() use($place) {
			return Status::wherePlaceId($place->id)
				->whereNull('uri')
				->whereScope('public')
				->orderByDesc('created_at')
				->limit(60)
				->pluck('id');
		});

		$posts = $posts->map(function($id) {
			return StatusService::get($id);
		})
		->filter()
		->values();

		return [
			'place' =>
			[
				'id' => $place->id,
				'name' => $place->name,
				'slug' => $place->slug,
				'country' => $place->country,
				'lat' => $place->lat,
				'long' => $place->long
			],
		'posts' => $posts];
	}

	public function moderatePost(Request $request, $id)
	{
		abort_if(!$request->user(), 403);
		abort_if($request->user()->is_admin != true, 403);

		if(config('pixelfed.bouncer.cloud_ips.ban_signups')) {
			abort_if(BouncerService::checkIp($request->ip()), 404);
		}

		$this->validate($request, [
			'action' => 'required|in:cw,mark-public,mark-unlisted,mark-private,mark-spammer,delete'
		]);

		$action = $request->input('action');
		$status = Status::find($id);

		if(!$status) {
			return response()->json(['error' => 'Cannot find status'], 400);
		}

		if($status->uri == null) {
			if($status->profile->user && $status->profile->user->is_admin) {
				return response()->json(['error' => 'Cannot moderate admin accounts'], 400);
			}
		}

		if($action == 'mark-spammer') {
			$status->profile->update([
				'unlisted' => true,
				'cw' => true,
				'no_autolink' => true
			]);

			Status::whereProfileId($status->profile_id)
				->get()
				->each(function($s) {
					if(in_array($s->scope, ['public', 'unlisted'])) {
						$s->scope = 'private';
						$s->visibility = 'private';
					}
					$s->is_nsfw = true;
					$s->save();
					StatusService::del($s->id, true);
				});

			Cache::forget('pf:bouncer_v0:exemption_by_pid:' . $status->profile_id);
			Cache::forget('pf:bouncer_v0:recent_by_pid:' . $status->profile_id);
			Cache::forget('admin-dash:reports:spam-count');
		} else if ($action == 'cw') {
			$state = $status->is_nsfw;
			$status->is_nsfw = !$state;
			$status->save();
			StatusService::del($status->id);
		} else if ($action == 'mark-public') {
			$state = $status->scope;
			$status->scope = 'public';
			$status->visibility = 'public';
			$status->save();
			StatusService::del($status->id, true);
			if($state !== 'public') {
				if($status->uri) {
					if($status->in_reply_to_id == null && $status->reblog_of_id == null) {
						NetworkTimelineService::add($status->id);
					}
				} else {
					if($status->in_reply_to_id == null && $status->reblog_of_id == null) {
						PublicTimelineService::add($status->id);
					}
				}
			}
		} else if ($action == 'mark-unlisted') {
			$state = $status->scope;
			$status->scope = 'unlisted';
			$status->visibility = 'unlisted';
			$status->save();
			StatusService::del($status->id);
			if($state == 'public') {
				PublicTimelineService::del($status->id);
				NetworkTimelineService::del($status->id);
			}
		} else if ($action == 'mark-private') {
			$state = $status->scope;
			$status->scope = 'private';
			$status->visibility = 'private';
			$status->save();
			StatusService::del($status->id);
			if($state == 'public') {
				PublicTimelineService::del($status->id);
				NetworkTimelineService::del($status->id);
			}
		} else if ($action == 'delete') {
			PublicTimelineService::del($status->id);
			NetworkTimelineService::del($status->id);
			Cache::forget('_api:statuses:recent_9:' . $status->profile_id);
			Cache::forget('profile:status_count:' . $status->profile_id);
			Cache::forget('profile:embed:' . $status->profile_id);
			StatusService::del($status->id, true);
			Cache::forget('profile:status_count:'.$status->profile_id);
			$status->uri ? RemoteStatusDelete::dispatch($status) : StatusDelete::dispatch($status);
			return [];
		}

		Cache::forget('_api:statuses:recent_9:'.$status->profile_id);

		return StatusService::get($status->id, false);
	}

	public function getWebSettings(Request $request)
	{
		abort_if(!$request->user(), 403);
        $uid = $request->user()->id;
        $settings = UserSetting::firstOrCreate([
            'user_id' => $uid
        ]);
        if(!$settings->other) {
            return [];
        }
		return $settings->other;
	}

    public function setWebSettings(Request $request)
    {
        abort_if(!$request->user(), 403);
        $this->validate($request, [
            'field' => 'required|in:enable_reblogs,hide_reblog_banner',
            'value' => 'required'
        ]);
        $field = $request->input('field');
        $value = $request->input('value');
        $settings = UserSetting::firstOrCreate([
            'user_id' => $request->user()->id
        ]);
        if(!$settings->other) {
            $other = [];
        } else {
            $other = $settings->other;
        }
        $other[$field] = $value;
        $settings->other = $other;
        $settings->save();

        return [200];
    }
}
