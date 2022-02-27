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
		<li>
			<strong>APP_URL:</strong>
			<span>{{config_cache('app.url')}}</span>
		</li>
		<li>
			<strong>APP_DOMAIN:</strong>
			<span>{{config_cache('pixelfed.domain.app')}}</span>
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
			<strong>PHP:</strong>
			<span>{{phpversion()}}</span>
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
			<strong>PHP-{{$ext}}:</strong>
			<span>Not installed/loaded</span>
		</li>
		@endif
		@endforeach
		<li>
			<strong>Database:</strong>
			@php($v = explode(' ', DB::select('select version() as version')[0]->version))
			<span>{{config('database.default')}} ({{count($v) == 1 ? $v[0] : $v[1]}})</span>
		</li>
		<li>
			<strong>Bootstrap:</strong>
			<span>{{is_writable(base_path('bootstrap/')) ? 'Writable' : 'Not writable'}}</span>
		</li>
		<li>
			<strong>Storage:</strong>
			<span>{{is_writable(base_path('storage/')) ? 'Writable' : 'Not writable'}}</span>
		</li>
		<li>
			<strong>Image Driver:</strong>
			<span>{{ config('image.driver') }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">REDIS</span> Ping:</strong>
			<span>{{ \Illuminate\Support\Facades\Redis::command('ping') ? '✅' : '❌' }}</span>
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
		<li>
			<strong><span class="badge badge-primary">APP</span> Cache Driver:</strong>
			<span>{{ config_cache('cache.default') }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">APP</span> Mail Driver:</strong>
			<span>{{ config_cache('mail.driver') }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">APP</span> Mail Host:</strong>
			<span>{{ config_cache('mail.host') ? substr(config_cache('mail.host'), 0, 5) . str_repeat('*', strlen(config_cache('mail.host')) - 5) : 'undefined' }}</span>
		</li>
		@if(config_cache('mail.driver') == 'mailgun')
		<li>
			<strong><span class="badge badge-primary">APP</span> Mailgun Domain:</strong>
			<span>{{ config_cache('services.mailgun.domain') ?? 'undefined' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">APP</span> Mailgun Secret:</strong>
			<span>{{ config_cache('services.mailgun.secret') ? str_repeat('*', strlen(config_cache('services.mailgun.secret'))) : 'undefined' }}</span>
		</li>
		@endif
		@if(config_cache('mail.driver') == 'ses')
		<li>
			<strong><span class="badge badge-primary">APP</span> SES Key:</strong>
			<span>{{ config_cache('services.ses.key') ? str_repeat('*', strlen(config_cache('services.ses.key'))) : 'undefined' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">APP</span> SES Secret:</strong>
			<span>{{ config_cache('services.ses.secret') ? str_repeat('*', strlen(config_cache('services.ses.secret'))) : 'undefined' }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">APP</span> SES Region:</strong>
			<span>{{ config_cache('services.ses.region') ?? 'undefined' }}</span>
		</li>
		@endif
		<li>
			<strong><span class="badge badge-primary">APP</span> Queue Driver:</strong>
			<span>{{ config_cache('queue.default') }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">APP</span> Session Driver:</strong>
			<span>{{ config_cache('session.driver') }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">APP</span> Session Lifetime:</strong>
			<span>{{ config_cache('session.lifetime') }}</span>
		</li>
		<li>
			<strong><span class="badge badge-primary">APP</span> Session Domain:</strong>
			<span>{{ config_cache('session.domain') }}</span>
		</li>
		<li>
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
