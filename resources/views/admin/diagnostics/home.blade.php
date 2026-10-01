@extends('admin.partial.template-full')

@section('section')
</div>
<div class="header bg-primary pb-3 mt-n4">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-12 col-lg-4">
                    <p class="display-1 text-white d-inline-block mb-0">Diagnostics</p>
                </div>
                <div class="col-12 col-lg-8 d-flex flex-column flex-md-row pt-3 pt-md-0" style="gap: 10px;">
                    <div class="flex-grow-1">
                        <a
                            class="btn btn-outline-white btn-lg btn-block px-3 mb-0 copy-information"
                            href="#">
                            <i class="far fa-clipboard mr-1"></i>
                            Copy Diagnostics
                        </a>
                    </div>
                    <div class="flex-grow-1">
                        <a
                            class="btn btn-outline-white btn-lg btn-block px-3 mb-0 copy-information"
                            href="#">
                            <i class="far fa-chart-network mr-1"></i>
                            Federation Test
                        </a>
                    </div>
                    <div class="flex-grow-1">

                        <a
                            class="btn btn-outline-white btn-lg btn-block px-3 mb-0 copy-information"
                            href="#">
                            <i class="far fa-mobile mr-1"></i>
                            Mobile App Test
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid mt-5">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="pb-3 border-bottom">
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
                			<span>{{ (bool) config_cache('pixelfed.oauth_enabled') ? '✅ true' : '❌ false' }}</span>
                		</li>
                		<li>
                			<strong><span class="badge badge-primary">OAUTH</span> token_expiration</strong>
                			<span>{{ config_cache('instance.oauth.token_expiration') }} days</span>
                		</li>
                		<li>
                			<strong><span class="badge badge-primary">OAUTH</span> public key exists: </strong>
                			<span>{{ file_exists(storage_path('oauth-public.key')) || config_cache('passport.public_key') ? '✅ true' : '❌ false' }}</span>
                		</li>
                		<li>
                			<strong><span class="badge badge-primary">OAUTH</span> private key exists: </strong>
                			<span>{{ file_exists(storage_path('oauth-private.key')) || config_cache('passport.private_key') ? '✅ true' : '❌ false' }}</span>
                		</li>

                		<hr>
                		<p class="font-weight-bold text-muted">
                			Important Information
                		</p>


                		<li>
                			<strong>Version:</strong>
                			<span>{{config('pixelfed.version')}}</span>
                		</li>

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

                    </ul>
                </div>
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
