@extends('admin.partial.template-full')

@section('section')
<div class="title mb-4">
	<h3 class="font-weight-bold">Diagnostics</h3>
	<p class="lead mb-0">Instance diagnostics</p>
</div>

<div class="pb-3 border-bottom">
	<p class="font-weight-bold text-muted">
		Information
		<span class="small text-primary ml-3 copy-information cursor-pointer text-uppercase font-weight-bold">Copy</span>
	</p>
	<ul class="information">
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
			<strong><span class="badge badge-primary">REDIS</span> Ping:</strong>
			<span>{{ \Illuminate\Support\Facades\Redis::command('ping') ? 'Pong ✅' : 'Not Responding ❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">ACTIVITYPUB</span> instance actor created: </strong>
			<span>{{ \App\Models\InstanceActor::count() ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">ACTIVITYPUB</span> instance actor cached: </strong>
			<span>{{ Cache::get(\App\Models\InstanceActor::PROFILE_KEY) ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">OAUTH</span> enabled: </strong>
			<span>{{ config_cache('pixelfed.oauth_enabled') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">OAUTH</span> token_expiration</strong>
			<span>{{ config_cache('instance.oauth.token_expiration') }} days</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">OAUTH</span> public key exists: </strong>
			<span>{{ file_exists(storage_path('oauth-public.key')) ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">OAUTH</span> private key exists: </strong>
			<span>{{ file_exists(storage_path('oauth-private.key')) ? '✅' : '❌' }}</span>
		</li>		
		
		<hr>
		<p class="font-weight-bold text-muted">
			Important Information
		</p>

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

		<!-- <li>
			<div class="tt">
				<strong><span class="badge badge-primary">CONFIG</span> pixelfed: </strong>
				<span class="text-truncate">{!! json_encode(config_cache('pixelfed'), JSON_UNESCAPED_SLASHES) !!}</span>
			</div>
		</li>
		<li>
			<div class="tt">
				<strong><span class="badge badge-primary">CONFIG</span> federation: </strong>
				<span class="text-truncate">{!! json_encode(config_cache('federation'), JSON_UNESCAPED_SLASHES) !!}</span>
			</div>
		</li> -->



		<hr>
		<p class="font-weight-bold text-muted">
			PHP Variables
		</p>
		<li>
			<strong>PHP:</strong>
			<span>{{phpversion()}}</span>
		</li>

		<li>
			<strong><span class="badge badge-primary">PHP</span> memory_limit:</strong>
			<span>{{ ini_get('memory_limit') }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PHP</span> post_max_size:</strong>
			<span>{{ ini_get('post_max_size') }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PHP</span> upload_max_filesize:</strong>
			<span>{{ ini_get('upload_max_filesize') }}</span>
		</li>


	<hr>
	<p class="font-weight-bold text-muted">
		Pixelfed Variables (No Secrets)
	</p>

		<li>
			<strong><span class="badge badge-primary">APP</span> APP_NAME:</strong>
			<span>{{config('app.name')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">APP</span> APP_ENV:</strong>
			<span>{{config('app.env')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">APP</span> APP_DEBUG:</strong>
			<span>{{config('app.debug') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">APP</span> APP_URL:</strong>
			<span>{{config('app.url')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">APP</span> APP_TIMEZONE:</strong>
			<span>na</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">APP</span> APP_LOCALE:</strong>
			<span>{{config('app.locale')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">APP</span> APP_FALLBACK_LOCALE:</strong>
			<span>{{config('app.fallback_locale')}}</span>
		</li>

		<li>
			<strong><span class="badge badge-primary">BROADCASTING</span> BROADCAST_DRIVER:</strong>
			<span>{{config('broadcasting.default')}}</span>
		</li>

		<li>
			<strong><span class="badge badge-primary">CACHE</span> CACHE_DRIVER:</strong>
			<span>{{config('cache.default')}}</span>
		</li>

		<li>
			<strong><span class="badge badge-primary">CAPTCHA</span> CAPTCHA_ENABLED:</strong>
			<span>{{ config_cache('captcha.enabled') ? '✅' : '❌' }}</span>
		</li>

		<li>
			<strong><span class="badge badge-primary">DATABASE</span> DB_CONNECTION:</strong>
			<span>{{config('database.default')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">DATABASE</span> REDIS_CLIENT:</strong>
			<span>{{config('database.redis.client')}}</span>
		</li>

		<li>
			<strong><span class="badge badge-primary">EXP</span> EXP_LC:</strong>
			<span>{{config('exp.lc') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">EXP</span> EXP_TOP:</strong>
			<span>{{config('exp.top') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">EXP</span> EXP_POLLS:</strong>
			<span>{{config('exp.polls') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">EXP</span> EXP_CPT:</strong>
			<span>{{config('exp.cached_public_timeline') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">EXP</span> EXP_GPS:</strong>
			<span>{{config('exp.gps') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">EXP</span> EXP_EMC:</strong>
			<span>{{config('exp.emc') ? '✅' : '❌' }}</span>
		</li>

		<li>
			<strong><span class="badge badge-primary">FEDERATION</span> ACTIVITY_PUB:</strong>
			<span>{{config('federation.activitypub.enabled') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">FEDERATION</span> AP_OUTBOX:</strong>
			<span>{{config('federation.activitypub.outbox') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">FEDERATION</span> AP_INBOX:</strong>
			<span>{{config('federation.activitypub.inbox') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">FEDERATION</span> AP_SHAREDINBOX:</strong>
			<span>{{config('federation.activitypub.sharedInbox') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">FEDERATION</span> AP_REMOTE_FOLLOW:</strong>
			<span>{{config('federation.activitypub.remoteFollow') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">FEDERATION</span> ACTIVITYPUB_DELIVERY_TIMEOUT:</strong>
			<span>{{config('federation.activitypub.delivery.timeout')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">FEDERATION</span> ACTIVITYPUB_DELIVERY_CONCURRENCY:</strong>
			<span>{{config('federation.activitypub.delivery.concurrency')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">FEDERATION</span> AP_LOGGER_ENABLED:</strong>
			<span>{{config('federation.activitypub.delivery.logger.enabled') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">FEDERATION</span> ATOM_FEEDS:</strong>
			<span>{{config('federation.atom.enabled') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">FEDERATION</span> REMOTE_AVATARS:</strong>
			<span>{{config('federation.avatars.store_local') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">FEDERATION</span> NODEINFO:</strong>
			<span>{{config('federation.nodeinfo.enabled') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">FEDERATION</span> WEBFINGER:</strong>
			<span>{{config('federation.webfinger.enabled') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">FEDERATION</span> PF_NETWORK_TIMELINE:</strong>
			<span>{{config('federation.network_timeline') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">FEDERATION</span> CUSTOM_EMOJI:</strong>
			<span>{{config('federation.custom_emoji.enabled') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">FEDERATION</span> CUSTOM_EMOJI_MAX_SIZE:</strong>
			<span>{{config('federation.custom_emoji.max_size')}}</span>
		</li>

		<li>
			<strong><span class="badge badge-primary">FILESYSTEMS</span> FILESYSTEM_DRIVER:</strong>
			<span>{{config('filesystems.default')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">FILESYSTEMS</span> FILESYSTEM_CLOUD:</strong>
			<span>{{config('filesystems.cloud')}}</span>
		</li>

		<li>
			<strong><span class="badge badge-primary">HASHING</span> BCRYPT_COST:</strong>
			<span>{{config('hashing.bcrypt.rounds')}}</span>
		</li>

		<li>
			<strong><span class="badge badge-primary">HORIZON</span> HORIZON_PREFIX:</strong>
			<span>{{config('horizon.prefix')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">HORIZON</span> HORIZON_MEMORY_LIMIT:</strong>
			<span>{{config('horizon.memory_limit')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">HORIZON</span> HORIZON_BALANCE_STRATEGY:</strong>
			<span>{{config('horizon.environments.production.supervisor-1.balance')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">HORIZON</span> HORIZON_MIN_PROCESSES:</strong>
			<span>{{config('horizon.environments.production.supervisor-1.minProcesses')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">HORIZON</span> HORIZON_MAX_PROCESSES:</strong>
			<span>{{config('horizon.environments.production.supervisor-1.maxProcesses')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">HORIZON</span> HORIZON_SUPERVISOR_MEMORY:</strong>
			<span>{{config('horizon.environments.production.supervisor-1.memory')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">HORIZON</span> HORIZON_SUPERVISOR_TRIES:</strong>
			<span>{{config('horizon.environments.production.supervisor-1.tries')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">HORIZON</span> HORIZON_SUPERVISOR_NICE:</strong>
			<span>{{config('horizon.environments.production.supervisor-1.nice')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">HORIZON</span> HORIZON_SUPERVISOR_TIMEOUT:</strong>
			<span>{{config('horizon.environments.production.supervisor-1.timeout')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">HORIZON</span> HORIZON_DARKMODE:</strong>
			<span>{{config('horizon.darkmode') ? '✅' : '❌' }}</span>
		</li>

		<li>
			<strong><span class="badge badge-primary">IMAGE</span> IMAGE_DRIVER (gd/imagick):</strong>
			<span>{{config('image.driver')}}</span>
		</li>

		<li>
			<strong><span class="badge badge-primary">IMAGE OPTIMIZER</span> IMAGE_QUALITY: </strong>
			<span>na</span>
		</li>

		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> INSTANCE_DESCRIPTION:</strong>
			<span>{{config('instance.description')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> INSTANCE_CONTACT_FORM:</strong>
			<span>{{config('instance.contact.enabled') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> INSTANCE_CONTACT_MAX_PER_DAY:</strong>
			<span>{{config('instance.contact.max_per_day')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> INSTANCE_DISCOVER_PUBLIC:</strong>
			<span>{{config('instance.discover.public') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> EXP_LOOPS:</strong>
			<span>{{config('instance.discover.loops.enabled') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> INSTANCE_PUBLIC_HASHTAGS:</strong>
			<span>{{config('instance.discover.tags.is_public') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> INSTANCE_CONTACT_EMAIL:</strong>
			<span>{{config('instance.email')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> INSTANCE_PUBLIC_LOCAL_TIMELINE:</strong>
			<span>{{config('instance.timeline.local.is_public') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> PAGE_404_HEADER:</strong>
			<span>{{config('instance.page.404.header')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> PAGE_404_BODY:</strong>
			<span>{{config('instance.page.404.body')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> PAGE_503_HEADER:</strong>
			<span>{{config('instance.page.503.header')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> PAGE_503_BODY:</strong>
			<span>{{config('instance.page.503.body')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> BANNED_USERNAMES:</strong>
			<span>{{config('instance.username.banned')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> USERNAME_REMOTE_FORMAT:</strong>
			<span>{{config('instance.username.remote.format')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> USERNAME_REMOTE_CUSTOM_TEXT:</strong>
			<span>{{config('instance.username.remote.custom')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> STORIES_ENABLED:</strong>
			<span>{{config('instance.stories.enabled') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> RESTRICTED_INSTANCE:</strong>
			<span>{{config('instance.restricted.enabled') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> OAUTH_TOKEN_DAYS:</strong>
			<span>{{config('instance.oauth.token_expiration')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> OAUTH_REFRESH_DAYS:</strong>
			<span>{{config('instance.oauth.refresh_expiration')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> OAUTH_PAT_ENABLED:</strong>
			<span>{{config('instance.oauth.pat.enabled') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> OAUTH_PAT_ID:</strong>
			<span>{{config('instance.oauth.pat.id')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> ENABLE_COVID_LABEL:</strong>
			<span>{{config('instance.label.covid.enabled') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> COVID_LABEL_URL:</strong>
			<span>{{config('instance.label.covid.url')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> COVID_LABEL_ORG:</strong>
			<span>{{config('instance.label.covid.org')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">INSTANCE</span> ENABLE_CONFIG_CACHE:</strong>
			<span>{{config('instance.enable_cc') ? '✅' : '❌' }}</span>
		</li>


		<li>
			<strong><span class="badge badge-primary">LDAP</span> LDAP_CONNECTION:</strong>
			<span>{{config('ldap.default')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">LDAP</span> LDAP_LOGGING:</strong>
			<span>{{config('ldap.logging') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">LDAP</span> LDAP_CACHE:</strong>
			<span>{{config('ldap.cache.enabled') ? '✅' : '❌' }}</span>
		</li>

		<li>
			<strong><span class="badge badge-primary">LOGGING</span> LOG_CHANNEL:</strong>
			<span>{{config('logging.default')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">LOGGING</span> LOG_LEVEL (stack):</strong>
			<span>{{config('logging.channels.single.level')}}</span>
		</li>

		<li>
			<strong><span class="badge badge-primary">MAIL</span> MAIL_DRIVER:</strong>
			<span>{{config('mail.driver')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">MAIL</span> MAIL_HOST:</strong>
			<span>{{config('mail.host')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">MAIL</span> MAIL_PORT:</strong>
			<span>{{config('mail.port')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">MAIL</span> MAIL_FROM_ADDRESS:</strong>
			<span>{{config('mail.from.address')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">MAIL</span> MAIL_FROM_NAME:</strong>
			<span>{{config('mail.from.name')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">MAIL</span> MAIL_ENCRYPTION:</strong>
			<span>{{config('mail.encryption')}}</span>
		</li>

		<li>
			<strong><span class="badge badge-primary">MEDIA</span> MEDIA_EXIF_DATABASE:</strong>
			<span>{{config('media.exif.batabase') ? '✅' : '❌' }}</span>
		</li>

		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> ADMIN_DOMAIN:</strong>
			<span>{{config('pixelfed.domain.admin')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> APP_DOMAIN:</strong>
			<span>{{config('pixelfed.domain.app')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> MEMORY_LIMIT:</strong>
			<span>{{config('pixelfed.memory_limit')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> OPEN_REGISTRATION:</strong>
			<span>{{config('pixelfed.open_registration') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> MAX_ACCOUNT_SIZE (KB):</strong>
			<span>{{config('pixelfed.max_account_size')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> MAX_PHOTO_SIZE (KB):</strong>
			<span>{{config('pixelfed.max_photo_size')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> MAX_AVATAR_SIZE (KB):</strong>
			<span>{{config('pixelfed.max_avatar_size')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> MAX_CAPTION_LENGTH:</strong>
			<span>{{config('pixelfed.max_caption_length')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> MAX_BIO_LENGTH:</strong>
			<span>{{config('pixelfed.max_bio_length')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> MAX_NAME_LENGTH:</strong>
			<span>{{config('pixelfed.max_name_length')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> MIN_PASSWORD_LENGTH:</strong>
			<span>{{config('pixelfed.min_password_length')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> MAX_ALBUM_LENGTH:</strong>
			<span>{{config('pixelfed.max_album_length')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> ENFORCE_EMAIL_VERIFICATION:</strong>
			<span>{{config('pixelfed.enforce_email_verification') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> IMAGE_QUALITY (1-100):</strong>
			<span>{{config('pixelfed.image_quality')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> ACCOUNT_DELETION:</strong>
			<span>{{config('pixelfed.account_deletion') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> ACCOUNT_DELETE_AFTER:</strong>
			<span>{{config('pixelfed.account_delete_after') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> PF_ENABLE_CLOUD:</strong>
			<span>{{config('pixelfed.cloud_storage') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> PF_MAX_USERS:</strong>
			<span>{{config('pixelfed.max_users')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> PF_OPTIMIZE_IMAGES:</strong>
			<span>{{config('pixelfed.optimize_image') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> PF_OPTIMIZE_VIDEOS:</strong>
			<span>{{config('pixelfed.optimize_video') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> PF_USER_INVITES:</strong>
			<span>{{config('pixelfed.user_invites.enabled') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> PF_USER_INVITES_TOTAL_LIMIT:</strong>
			<span>{{config('pixelfed.user_invites.limit.total')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> PF_USER_INVITES_DAILY_LIMIT:</strong>
			<span>{{config('pixelfed.user_invites.limit.daily')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> PF_USER_INVITES_MONTHLY_LIMIT:</strong>
			<span>{{config('pixelfed.user_invites.limit.monthly')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> PF_MAX_COLLECTION_LENGTH:</strong>
			<span>{{config('pixelfed.max_collection_length')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> MEDIA_TYPES:</strong>
			<span>{{config('pixelfed.media_types')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> LIMIT_ACCOUNT_SIZE:</strong>
			<span>{{config('pixelfed.enforce_account_limit')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> IMPORT_INSTAGRAM:</strong>
			<span>{{config('pixelfed.import.instagram.enabled') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> IMPORT_INSTAGRAM_POST_LIMIT:</strong>
			<span>{{config('pixelfed.import.instagram.limits.posts')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> IMPORT_INSTAGRAM_SIZE_LIMIT:</strong>
			<span>{{config('pixelfed.import.instagram.limits.size')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> OAUTH_ENABLED:</strong>
			<span>{{config('pixelfed.oauth_enabled') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> PF_BOUNCER_ENABLED:</strong>
			<span>{{config('pixelfed.bouncer.enabled') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> PF_MEDIA_FAST_PROCESS:</strong>
			<span>{{config('pixelfed.media_fast_process') ? '✅' : '❌' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">PIXELFED</span> PF_MEDIA_MAX_ALTTEXT_LENGTH:</strong>
			<span>{{config('pixelfed.max_altext_length')}}</span>
		</li>

		<li>
			<strong><span class="badge badge-primary">PURIFY</span> RESTRICT_HTML_TYPES (true/false):</strong>
			<span>{{config('purify.settings.HTML.Allowed')}}</span>
		</li>

		<li>
			<strong><span class="badge badge-primary">QUEUE</span> QUEUE_DRIVER (redis/database/sync):</strong>
			<span>{{config('queue.default')}}</span>
		</li>

		<li>
			<strong><span class="badge badge-primary">SESSION</span> SESSION_DRIVER:</strong>
			<span>{{config('session.driver')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">SESSION</span> SESSION_LIFETIME:</strong>
			<span>{{config('session.lifetime')}}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">SESSION</span> SESSION_DOMAIN:</strong>
			<span>{{config('session.domain')}}</span>
		</li>

		<li>
			<strong><span class="badge badge-primary">TRUSTEDPROXY</span> TRUST_PROXIES:</strong>
			<span>{{config('trustedproxy.proxies')}}</span>
		</li>
		
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
