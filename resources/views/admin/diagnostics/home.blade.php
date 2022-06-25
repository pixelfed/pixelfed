@extends('admin.partial.template-full')

@section('section')
<div class="title mb-4">
	<h3 class="font-weight-bold">Diagnostics</h3>
</div>

<div class="pb-3 border-bottom">
	<p class="font-weight-bold text-muted">
		Information
		<span class="small text-primary ml-3 copy-information cursor-pointer text-uppercase font-weight-bold">Copy</span>
	</p>

	<div class="information">


	<ul>
		<p class="font-weight-bold text-muted">
			Troubleshooting
		</p>

		<li>
			<strong>Bootstrap:</strong>
			<span>{{is_writable(base_path('bootstrap/')) ? 'Writable ✅' : 'Not writable ❌'}}</span>
		</li>
		<li>
			<strong>Storage:</strong>
			<span>{{is_writable(base_path('storage/')) ? 'Writable ✅' : 'Not writable ❌'}}</span>
		</li>

		@foreach([
			'bcmath',
			'gd',
			'imagick',
			'ctype',
			'curl',
			'intl',
			'json',
			'mbstring',
			'openssl',
			'redis'
		] as $ext)
			@if(!extension_loaded($ext))
				<li>
					<strong>PHP Module {{$ext}}:</strong>
					<span>Not installed/Not loaded ❌</span>
				</li>
			@endif
		@endforeach

		<li>
			<strong><span class="badge badge-primary">DATABASE</span> Ping:</strong>
			<span>{{ \DB::connection()->getPDO() ? 'Pong! Connected to DB "' . \DB::connection()->getDatabaseName() . '" ✅' : 'DB Not Responding ❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">REDIS</span> Ping:</strong>
			<span>{{ \Illuminate\Support\Facades\Redis::command('ping') ? 'Pong! Connected to Redis ✅' : 'Redis Not Responding ❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">ACTIVITYPUB</span> instance actor created: </strong>
			<span>{{ \App\Models\InstanceActor::count() ? '✅ true' : '❌ false' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">ACTIVITYPUB</span> instance actor cached: </strong>
			<span>{{ Cache::get(\App\Models\InstanceActor::PROFILE_KEY) ? '✅ true' : '❌ false' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">OAUTH</span> enabled: </strong>
			<span>{{ config_cache('pixelfed.oauth_enabled') ? '✅ true' : '❌ false' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">OAUTH</span> token_expiration</strong>
			<span>{{ config_cache('instance.oauth.token_expiration') }} days</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">OAUTH</span> public key exists: </strong>
			<span>{{ file_exists(storage_path('oauth-public.key')) ? '✅ true' : '❌ false' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">OAUTH</span> private key exists: </strong>
			<span>{{ file_exists(storage_path('oauth-private.key')) ? '✅ true' : '❌ false' }}</span>
		</li>		
		
		<hr>
		<p class="font-weight-bold text-muted">
			Important Information
		</p>


		@if(function_exists('shell_exec'))
			<li>
				<strong>Version:</strong>
				<span>{{config('pixelfed.version')}}-{{ @shell_exec('git log --pretty="%h" -n1 HEAD') ?? 'unknown git commit' }}</span>
			</li>
		@else
			<li>
				<strong>Version:</strong>
				<span>{{config('pixelfed.version')}}</span>
			</li>
		@endif

		<li>
			<strong>Database:</strong>
			@php($v = explode(' ', DB::select('select version() as version')[0]->version))
			<span>{{config('database.default')}} ({{count($v) == 1 ? $v[0] : $v[1]}})</span>
		</li>
		<li>
			<strong>APP_URL:</strong>
			<span>{{config_cache('app.url')}}</span>
		</li>
		<li>
			<strong>APP_DOMAIN:</strong>
			<span>{{config_cache('pixelfed.domain.app')}}</span>
		</li>
		<li>
			<strong>ADMIN_DOMAIN:</strong>
			<span>{{config_cache('pixelfed.domain.admin')}}</span>
		</li>
		<li>
			<strong>SESSION_DOMAIN:</strong>
			<span>{{config_cache('session.domain')}}</span>
		</li>

		<hr>
		<p class="font-weight-bold text-muted">
			PHP Variables
		</p>
		<li>
			<strong>PHP:</strong>
			<span>{{phpversion()}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PHP INI</span> memory_limit:</strong>
			<span>{{ ini_get('memory_limit') }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PHP INI</span> post_max_size:</strong>
			<span>{{ ini_get('post_max_size') }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PHP INI</span> upload_max_filesize:</strong>
			<span>{{ ini_get('upload_max_filesize') }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PHP INI</span> max_file_uploads:</strong>
			<span>{{ ini_get('max_file_uploads') }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PHP INI</span> max_execution_time:</strong>
			<span>{{ ini_get('max_execution_time') }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PHP INI</span> max_input_time:</strong>
			<span>{{ ini_get('max_input_time') }}</span>
		</li>

		<li>
			<strong><span class="badge badge-primary">PHP INI</span> file_uploads (On):</strong>
			<span>{{ ini_get('file_uploads') }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PHP INI - Security</span> allow_url_fopen (true):</strong>
			<span>{{ ini_get('allow_url_fopen') }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PHP INI - Security</span> allow_url_include (false):</strong>
			<span>{{ ini_get('allow_url_include') }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PHP INI - Security</span> expose_php (false):</strong>
			<span>{{ ini_get('expose_php') }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PHP INI - Security</span> display_errors (false):</strong>
			<span>{{ ini_get('display_errors') }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PHP INI - Security</span> display_startup_errors (false):</strong>
			<span>{{ ini_get('display_startup_errors') }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PHP INI - Security</span> log_errors (true):</strong>
			<span>{{ ini_get('log_errors') }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PHP INI - Security</span> ignore_repeated_errors (false):</strong>
			<span>{{ ini_get('ignore_repeated_errors') }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PHP INI - Security</span> disable_functions:</strong>
			<span>{{ ini_get('disable_functions') }}</span>
		</li>

	<hr>
	<p class="font-weight-bold text-muted">
		Pixelfed Variables (No Secrets)
	</p>
	<table style="width:100%" class="table">
	<thead class="bg-light">
	<tr>
		<th width="5%" scope="col" class="border-0 text-dark">CONFIG</th>
		<th width="20%"scope="col" class="border-0 text-dark">Variable Name</th>
		<th width="40%"scope="col" class="border-0 text-dark">Details</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td><span class="badge badge-primary">APP</span></td>
		<td><strong>APP_NAME</strong></td>
		<td><span>"{{config_cache('app.name')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">APP</span></td>
		<td><strong>APP_ENV</strong></td>
		<td><span>"{{config_cache('app.env')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">APP</span></td>
		<td><strong>APP_DEBUG</strong></td>
		<td><span>{{config_cache('app.debug') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">APP</span></td>
		<td><strong>APP_URL</strong></td>
		<td><span>"{{config_cache('app.url')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">APP</span></td>
		<td><strong>APP_LOCALE</strong></td>
		<td><span>"{{config_cache('app.locale')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">APP</span></td>
		<td><strong>APP_FALLBACK_LOCALE</strong></td>
		<td><span>"{{config_cache('app.fallback_locale')}}"</span></td>
	</tr>

	<tr>
		<td><span class="badge badge-primary">BROADCASTING</span></td>
		<td><strong>BROADCAST_DRIVER</strong></td>
		<td><span>"{{config_cache('broadcasting.default')}}"</span></td>
	</tr>

	<tr>
		<td><span class="badge badge-primary">CACHE</span></td>
		<td><strong>CACHE_DRIVER</strong></td>
		<td><span>"{{config_cache('cache.default')}}"</span></td>
	</tr>

	<tr>
		<td><span class="badge badge-primary">CAPTCHA</span></td>
		<td><strong>CAPTCHA_ENABLED</strong></td>
		<td><span>{{ config_cache('captcha.enabled') ? '✅ true' : '❌ false' }}</span></td>
	</tr>

	<tr>
		<td><span class="badge badge-primary">DATABASE</span></td>
		<td><strong>DB_CONNECTION</strong></td>
		<td><span>"{{config_cache('database.default')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">DATABASE</span></td>
		<td><strong>REDIS_CLIENT</strong></td>
		<td><span>"{{config_cache('database.redis.client')}}"</span></td>
	</tr>

	<tr>
		<td><span class="badge badge-primary">EXP</span></td>
		<td><strong>EXP_LC</strong></td>
		<td><span>{{config_cache('exp.lc') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">EXP</span></td>
		<td><strong>EXP_TOP</strong></td>
		<td><span>{{config_cache('exp.top') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">EXP</span></td>
		<td><strong>EXP_POLLS</strong></td>
		<td><span>{{config_cache('exp.polls') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">EXP</span></td>
		<td><strong>EXP_CPT</strong></td>
		<td><span>{{config_cache('exp.cached_public_timeline') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">EXP</span></td>
		<td><strong>EXP_GPS</strong></td>
		<td><span>{{config_cache('exp.gps') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">EXP</span></td>
		<td><strong>EXP_EMC</strong></td>
		<td><span>{{config_cache('exp.emc') ? '✅ true' : '❌ false' }}</span></td>
	</tr>

	<tr>
		<td><span class="badge badge-primary">FEDERATION</span></td>
		<td><strong>ACTIVITY_PUB</strong></td>
		<td><span>{{config_cache('federation.activitypub.enabled') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">FEDERATION</span></td>
		<td><strong>AP_OUTBOX</strong></td>
		<td><span>{{config_cache('federation.activitypub.outbox') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">FEDERATION</span></td>
		<td><strong>AP_INBOX</strong></td>
		<td><span>{{config_cache('federation.activitypub.inbox') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">FEDERATION</span></td>
		<td><strong>AP_SHAREDINBOX</strong></td>
		<td><span>{{config_cache('federation.activitypub.sharedInbox') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">FEDERATION</span></td>
		<td><strong>AP_REMOTE_FOLLOW</strong></td>
		<td><span>{{config_cache('federation.activitypub.remoteFollow') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">FEDERATION</span></td>
		<td><strong>ACTIVITYPUB_DELIVERY_TIMEOUT</strong></td>
		<td><span>"{{config_cache('federation.activitypub.delivery.timeout')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">FEDERATION</span></td>
		<td><strong>ACTIVITYPUB_DELIVERY_CONCURRENCY</strong></td>
		<td><span>"{{config_cache('federation.activitypub.delivery.concurrency')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">FEDERATION</span></td>
		<td><strong>AP_LOGGER_ENABLED</strong></td>
		<td><span>{{config_cache('federation.activitypub.delivery.logger.enabled') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">FEDERATION</span></td>
		<td><strong>ATOM_FEEDS</strong></td>
		<td><span>{{config_cache('federation.atom.enabled') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">FEDERATION</span></td>
		<td><strong>REMOTE_AVATARS</strong></td>
		<td><span>{{config_cache('federation.avatars.store_local') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">FEDERATION</span></td>
		<td><strong>NODEINFO</strong></td>
		<td><span>{{config_cache('federation.nodeinfo.enabled') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">FEDERATION</span></td>
		<td><strong>WEBFINGER</strong></td>
		<td><span>{{config_cache('federation.webfinger.enabled') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">FEDERATION</span></td>
		<td><strong>PF_NETWORK_TIMELINE</strong></td>
		<td><span>{{config_cache('federation.network_timeline') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">FEDERATION</span></td>
		<td><strong>PF_NETWORK_TIMELINE_DAYS_FALLOFF</strong></td>
		<td><span>{{config('federation.network_timeline_days_falloff') }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">FEDERATION</span></td>
		<td><strong>CUSTOM_EMOJI</strong></td>
		<td><span>{{config_cache('federation.custom_emoji.enabled') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">FEDERATION</span></td>
		<td><strong>CUSTOM_EMOJI_MAX_SIZE</strong></td>
		<td><span>"{{config_cache('federation.custom_emoji.max_size')}}"</span></td>
	</tr>

	<tr>
		<td><span class="badge badge-primary">FILESYSTEMS</span></td>
		<td><strong>FILESYSTEM_DRIVER</strong></td>
		<td><span>"{{config_cache('filesystems.default')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">FILESYSTEMS</span></td>
		<td><strong>FILESYSTEM_CLOUD</strong></td>
		<td><span>"{{config_cache('filesystems.cloud')}}"</span></td>
	</tr>

	<tr>
		<td><span class="badge badge-primary">HASHING</span></td>
		<td><strong>BCRYPT_COST</strong></td>
		<td><span>"{{config_cache('hashing.bcrypt.rounds')}}"</span></td>
	</tr>

	<tr>
		<td><span class="badge badge-primary">HORIZON</span></td>
		<td><strong>HORIZON_PREFIX</strong></td>
		<td><span>"{{config_cache('horizon.prefix')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">HORIZON</span></td>
		<td><strong>HORIZON_MEMORY_LIMIT</strong></td>
		<td><span>"{{config_cache('horizon.memory_limit')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">HORIZON</span></td>
		<td><strong>HORIZON_BALANCE_STRATEGY</strong></td>
		<td><span>"{{config_cache('horizon.environments.production.supervisor-1.balance')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">HORIZON</span></td>
		<td><strong>HORIZON_MIN_PROCESSES</strong></td>
		<td><span>"{{config_cache('horizon.environments.production.supervisor-1.minProcesses')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">HORIZON</span></td>
		<td><strong>HORIZON_MAX_PROCESSES</strong></td>
		<td><span>"{{config_cache('horizon.environments.production.supervisor-1.maxProcesses')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">HORIZON</span></td>
		<td><strong>HORIZON_SUPERVISOR_MEMORY</strong></td>
		<td><span>"{{config_cache('horizon.environments.production.supervisor-1.memory')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">HORIZON</span></td>
		<td><strong>HORIZON_SUPERVISOR_TRIES</strong></td>
		<td><span>"{{config_cache('horizon.environments.production.supervisor-1.tries')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">HORIZON</span></td>
		<td><strong>HORIZON_SUPERVISOR_NICE</strong></td>
		<td><span>"{{config_cache('horizon.environments.production.supervisor-1.nice')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">HORIZON</span></td>
		<td><strong>HORIZON_SUPERVISOR_TIMEOUT</strong></td>
		<td><span>"{{config_cache('horizon.environments.production.supervisor-1.timeout')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">HORIZON</span></td>
		<td><strong>HORIZON_DARKMODE</strong></td>
		<td><span>{{config_cache('horizon.darkmode') ? '✅ true' : '❌ false' }}</span></td>
	</tr>

	<tr>
		<td><span class="badge badge-primary">IMAGE</span></td>
		<td><strong>IMAGE_DRIVER </strong></td>
		<td><span>"{{config_cache('image.driver')}}"</span></td>
	</tr>

	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>INSTANCE_DESCRIPTION</strong></td>
		<td><span>"{{config_cache('instance.description')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>INSTANCE_CONTACT_FORM</strong></td>
		<td><span>{{config_cache('instance.contact.enabled') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>INSTANCE_CONTACT_MAX_PER_DAY</strong></td>
		<td><span>"{{config_cache('instance.contact.max_per_day')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>INSTANCE_DISCOVER_PUBLIC</strong></td>
		<td><span>{{config_cache('instance.discover.public') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>EXP_LOOPS</strong></td>
		<td><span>{{config_cache('instance.discover.loops.enabled') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>INSTANCE_PUBLIC_HASHTAGS</strong></td>
		<td><span>{{config_cache('instance.discover.tags.is_public') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>INSTANCE_CONTACT_EMAIL</strong></td>
		<td><span>"{{config_cache('instance.email')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>INSTANCE_PUBLIC_LOCAL_TIMELINE</strong></td>
		<td><span>{{config_cache('instance.timeline.local.is_public') ? '✅ true' : '❌ false' }}</span></td>
	</tr>

	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>INSTANCE_NETWORK_TIMELINE_CACHED</strong></td>
		<td><span>{{config('instance.timeline.network.cached') }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>INSTANCE_NETWORK_TIMELINE_CACHE_DROPOFF</strong></td>
		<td><span>{{config('instance.timeline.network.cache_dropoff') }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>INSTANCE_NETWORK_TIMELINE_CACHE_MAX_HOUR_INGEST</strong></td>
		<td><span>{{config('instance.timeline.network.max_hours_old') }}</span></td>
	</tr>

	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>PAGE_404_HEADER</strong></td>
		<td><span>"{{config_cache('instance.page.404.header')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>PAGE_404_BODY</strong></td>
		<td><span>"{{config_cache('instance.page.404.body')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>PAGE_503_HEADER</strong></td>
		<td><span>"{{config_cache('instance.page.503.header')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>PAGE_503_BODY</strong></td>
		<td><span>"{{config_cache('instance.page.503.body')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>BANNED_USERNAMES</strong></td>
		<td><span>"{{config_cache('instance.username.banned')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>USERNAME_REMOTE_FORMAT</strong></td>
		<td><span>"{{config_cache('instance.username.remote.format')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>USERNAME_REMOTE_CUSTOM_TEXT</strong></td>
		<td><span>"{{config_cache('instance.username.remote.custom')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>STORIES_ENABLED</strong></td>
		<td><span>{{config_cache('instance.stories.enabled') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>RESTRICTED_INSTANCE</strong></td>
		<td><span>{{config_cache('instance.restricted.enabled') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>OAUTH_TOKEN_DAYS</strong></td>
		<td><span>"{{config_cache('instance.oauth.token_expiration')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>OAUTH_REFRESH_DAYS</strong></td>
		<td><span>"{{config_cache('instance.oauth.refresh_expiration')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>OAUTH_PAT_ENABLED</strong></td>
		<td><span>{{config_cache('instance.oauth.pat.enabled') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>OAUTH_PAT_ID</strong></td>
		<td><span>"{{config_cache('instance.oauth.pat.id')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>ENABLE_COVID_LABEL</strong></td>
		<td><span>{{config_cache('instance.label.covid.enabled') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>COVID_LABEL_URL</strong></td>
		<td><span>"{{config_cache('instance.label.covid.url')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>COVID_LABEL_ORG</strong></td>
		<td><span>"{{config_cache('instance.label.covid.org')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">INSTANCE</span></td>
		<td><strong>ENABLE_CONFIG_CACHE</strong></td>
		<td><span>{{config_cache('instance.enable_cc') ? '✅ true' : '❌ false' }}</span></td>
	</tr>

	<tr>
		<td><span class="badge badge-primary">LDAP</span></td>
		<td><strong>LDAP_CONNECTION</strong></td>
		<td><span>"{{config_cache('ldap.default')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">LDAP</span></td>
		<td><strong>LDAP_LOGGING</strong></td>
		<td><span>{{config_cache('ldap.logging') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">LDAP</span></td>
		<td><strong>LDAP_CACHE</strong></td>
		<td><span>{{config_cache('ldap.cache.enabled') ? '✅ true' : '❌ false' }}</span></td>
	</tr>

	<tr>
		<td><span class="badge badge-primary">LOGGING</span></td>
		<td><strong>LOG_CHANNEL</strong></td>
		<td><span>"{{config_cache('logging.default')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">LOGGING</span></td>
		<td><strong>LOG_LEVEL (stack)</strong></td>
		<td><span>"{{config_cache('logging.channels.single.level')}}"</span></td>
	</tr>

	<tr>
		<td><span class="badge badge-primary">MAIL</span></td>
		<td><strong>MAIL_DRIVER</strong></td>
		<td><span>"{{config_cache('mail.driver')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">MAIL</span></td>
		<td><strong>MAIL_HOST</strong></td>
		<td><span>"{{config_cache('mail.host')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">MAIL</span></td>
		<td><strong>MAIL_PORT</strong></td>
		<td><span>"{{config_cache('mail.port')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">MAIL</span></td>
		<td><strong>MAIL_FROM_ADDRESS</strong></td>
		<td><span>"{{config_cache('mail.from.address')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">MAIL</span></td>
		<td><strong>MAIL_FROM_NAME</strong></td>
		<td><span>"{{config_cache('mail.from.name')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">MAIL</span></td>
		<td><strong>MAIL_ENCRYPTION</strong></td>
		<td><span>"{{config_cache('mail.encryption')}}"</span></td>
	</tr>

	<tr>
		<td><span class="badge badge-primary">MEDIA</span></td>
		<td><strong>MEDIA_EXIF_DATABASE</strong></td>
		<td><span>{{config_cache('media.exif.batabase') ? '✅ true' : '❌ false' }}</span></td>
	</tr>

	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>ADMIN_DOMAIN</strong></td>
		<td><span>"{{config_cache('pixelfed.domain.admin')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>APP_DOMAIN</strong></td>
		<td><span>"{{config_cache('pixelfed.domain.app')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>MEMORY_LIMIT</strong></td>
		<td><span>"{{config_cache('pixelfed.memory_limit')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>OPEN_REGISTRATION</strong></td>
		<td><span>{{config_cache('pixelfed.open_registration') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>MAX_ACCOUNT_SIZE (KB)</strong></td>
		<td><span>"{{config_cache('pixelfed.max_account_size')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>MAX_PHOTO_SIZE (KB)</strong></td>
		<td><span>"{{config_cache('pixelfed.max_photo_size')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>MAX_AVATAR_SIZE (KB)</strong></td>
		<td><span>"{{config_cache('pixelfed.max_avatar_size')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>MAX_CAPTION_LENGTH</strong></td>
		<td><span>"{{config_cache('pixelfed.max_caption_length')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>MAX_BIO_LENGTH</strong></td>
		<td><span>"{{config_cache('pixelfed.max_bio_length')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>MAX_NAME_LENGTH</strong></td>
		<td><span>"{{config_cache('pixelfed.max_name_length')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>MIN_PASSWORD_LENGTH</strong></td>
		<td><span>"{{config_cache('pixelfed.min_password_length')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>MAX_ALBUM_LENGTH</strong></td>
		<td><span>"{{config_cache('pixelfed.max_album_length')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>ENFORCE_EMAIL_VERIFICATION</strong></td>
		<td><span>{{config_cache('pixelfed.enforce_email_verification') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>IMAGE_QUALITY (1-100)</strong></td>
		<td><span>"{{config_cache('pixelfed.image_quality')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>ACCOUNT_DELETION</strong></td>
		<td><span>{{config_cache('pixelfed.account_deletion') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>ACCOUNT_DELETE_AFTER</strong></td>
		<td><span>{{config_cache('pixelfed.account_delete_after') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>PF_ENABLE_CLOUD</strong></td>
		<td><span>{{config_cache('pixelfed.cloud_storage') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>PF_MAX_USERS</strong></td>
		<td><span>{{config_cache('pixelfed.max_users') ? config('pixelfed.max_users') : '❌ false'}}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>PF_OPTIMIZE_IMAGES</strong></td>
		<td><span>{{config_cache('pixelfed.optimize_image') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>PF_OPTIMIZE_VIDEOS</strong></td>
		<td><span>{{config_cache('pixelfed.optimize_video') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>PF_USER_INVITES</strong></td>
		<td><span>{{config_cache('pixelfed.user_invites.enabled') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>PF_USER_INVITES_TOTAL_LIMIT</strong></td>
		<td><span>"{{config_cache('pixelfed.user_invites.limit.total')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>PF_USER_INVITES_DAILY_LIMIT</strong></td>
		<td><span>"{{config_cache('pixelfed.user_invites.limit.daily')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>PF_USER_INVITES_MONTHLY_LIMIT</strong></td>
		<td><span>"{{config_cache('pixelfed.user_invites.limit.monthly')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>PF_MAX_COLLECTION_LENGTH</strong></td>
		<td><span>"{{config_cache('pixelfed.max_collection_length')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>MEDIA_TYPES</strong></td>
		<td><span>"{{config_cache('pixelfed.media_types')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>LIMIT_ACCOUNT_SIZE</strong></td>
		<td><span>{{config_cache('pixelfed.enforce_account_limit') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>IMPORT_INSTAGRAM</strong></td>
		<td><span>{{config_cache('pixelfed.import.instagram.enabled') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>IMPORT_INSTAGRAM_POST_LIMIT</strong></td>
		<td><span>"{{config_cache('pixelfed.import.instagram.limits.posts')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>IMPORT_INSTAGRAM_SIZE_LIMIT</strong></td>
		<td><span>"{{config_cache('pixelfed.import.instagram.limits.size')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>OAUTH_ENABLED</strong></td>
		<td><span>{{config_cache('pixelfed.oauth_enabled') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>PF_BOUNCER_ENABLED</strong></td>
		<td><span>{{config_cache('pixelfed.bouncer.enabled') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>PF_MEDIA_FAST_PROCESS</strong></td>
		<td><span>{{config_cache('pixelfed.media_fast_process') ? '✅ true' : '❌ false' }}</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">PIXELFED</span></td>
		<td><strong>PF_MEDIA_MAX_ALTTEXT_LENGTH</strong></td>
		<td><span>"{{config_cache('pixelfed.max_altext_length')}}"</span></td>
	</tr>

	<tr>
		<td><span class="badge badge-primary">PURIFY</span></td>
		<td><strong>RESTRICT_HTML_TYPES</strong></td>
		<td><span>BROKEN</span></td>
	</tr>

	<tr>
		<td><span class="badge badge-primary">QUEUE</span></td>
		<td><strong>QUEUE_DRIVER</strong></td>
		<td><span>"{{config_cache('queue.default')}}"</span></td>
	</tr>

	<tr>
		<td><span class="badge badge-primary">SESSION</span></td>
		<td><strong>SESSION_DRIVER</strong></td>
		<td><span>"{{config_cache('session.driver')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">SESSION</span></td>
		<td><strong>SESSION_LIFETIME</strong></td>
		<td><span>"{{config_cache('session.lifetime')}}"</span></td>
	</tr>
	<tr>
		<td><span class="badge badge-primary">SESSION</span></td>
		<td><strong>SESSION_DOMAIN</strong></td>
		<td><span>"{{config_cache('session.domain')}}"</span></td>
	</tr>

	<tr>
		<td><span class="badge badge-primary">TRUSTEDPROXY</span></td>
		<td><strong>TRUST_PROXIES</strong></td>
		<td><span>"{{config_cache('trustedproxy.proxies')}}"</span></td>
	</tr>
      </tbody>
      </table>
  </div>
  </ul>

</div>
<div class="pb-3 border-bottom">
	<div class="form-group mb-0">
		<div class="ml-n4 mr-n2 p-3">
			<label class="font-weight-bold text-muted">Decrypt Payload</label>
			<textarea class="form-control payload-input" rows="5" name="payload" placeholder="Enter payload here"></textarea>
			<p class="help-text small text-muted mt-3 mb-0">The payload is from the "Something went wrong" page, anyone can copy the payload for you to decrypt.<br />Contents are encrypted due to potential sensitive information.</p>
		</div>
	</div>


	<div class="form-group row">
		<div class="col-12">
			<button type="button" class="btn btn-primary font-weight-bold px-5 decrypt-payload">Decrypt</button>
		</div>
	</div>
</div>
@endsection

@push('scripts')
<style type="text/css">
	.tt {
		display: flex;
	}

	.information strong {
		margin-right: 5px;
	}

	.information .text-truncate {
		overflow: hidden;
		max-width: 200px;
	}
</style>
<script type="text/javascript" src="{{mix('js/components.js')}}"></script>
<script type="text/javascript">
	$('.decrypt-payload').on('click', function(e) {
		let payload = document.querySelector('.payload-input').value;
		axios.post('{{route('admin.diagnostics.decrypt')}}', {
			'payload': payload
		}).then(res => {
			swal(
				'Payload',
				res.data.decrypted,
				'info'
			);
			document.querySelector('.payload-input').value = '';
		}).catch(err => {
			swal(
				'Error',
				err.response.data.error,
				'error'
			);
		});
	});

	$('.copy-information').on('click', function(e) {
		let text = document.querySelector('.information').innerText;
		let payload = '=======================\n Pixelfed Instance Diagnostic v0.2 \n=======================\n' + text + '\n========= END =========\n';
		navigator.clipboard.writeText(payload);
		swal('Copied', 'Successfully copied diagnostic information to clipboard!', 'success');
	});
</script>
@endpush
