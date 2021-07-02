@extends('admin.partial.template')

@include('admin.settings.sidebar')

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
		<li>
			<strong>Version:</strong>
			<span>{{config('pixelfed.version')}}</span>
		</li>
		<li>
			<strong>PHP:</strong>
			<span>{{phpversion()}}</span>
		</li>
		@foreach([
			'bcmath',
			'ctype',
			'curl',
			'intl',
			'json',
			'mbstring',
			'openssl',
			'redis'
		] as $ext)
		<li>
			<strong>PHP-{{$ext}}:</strong>
			<span>{{extension_loaded($ext) ? 'Installed' : 'Not installed'}}</span>
		</li>
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
		let payload = '=======================\n Instance Diagnostic \n=======================\n' + text + '\n========= END =========\n';
		navigator.clipboard.writeText(payload);
		swal('Copied', 'Successfully copied diagnostic information to clipboard!', 'success');
	});
</script>
@endpush
