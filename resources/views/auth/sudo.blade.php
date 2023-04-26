@extends('layouts.blank')

@push('styles')
<link href="{{ mix('css/landing.css') }}" rel="stylesheet">
<link rel="preload" as="image" href="{{ url('/_landing/bg.jpg')}}" />
@endpush

@section('content')
<div class="page-wrapper">
	<div class="container mt-5">
	    <div class="row justify-content-center">
	        <div class="col-lg-5">
	            <div class="text-center">
	                <a href="/">
	                	<img src="/img/pixelfed-icon-white.svg" height="60px">
	            	</a>
	                <h1 class="pt-4 pb-1">Sudo Mode</h1>
	                <p class="font-weight-light lead pb-2">Confirm password to continue</p>
	            </div>
	            <div class="card bg-glass">
	                <div class="card-body">
	                    <form method="POST" id="sudoForm">
	                        @csrf

	                        <div class="form-group">
	                        	<label class="font-weight-bold small text-muted">Confirm Password</label>
	                            <input
	                            	id="password"
	                            	type="password"
	                            	class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}"
	                            	name="password"
	                            	autocomplete="new-password"
	                            	placeholder="{{__('Password')}}"
	                            	required>

	                            @if ($errors->has('password'))
	                                <span class="invalid-feedback">
	                                    <strong>{{ $errors->first('password') }}</strong>
	                                </span>
	                            @endif
	                        </div>

	                        <div class="form-group">
	                            <div class="custom-control custom-checkbox" id="trusted-device">
	                              <input type="checkbox" class="custom-control-input" name="trustDevice">
	                              <label class="custom-control-label text-muted" for="trusted-device">Trust this device and don't ask again</label>
	                            </div>
	                        </div>

	                        <div class="form-group row mb-0">
	                            <div class="col-md-12">
	                                <button
	                                	type="button"
	                                	id="sbtn"
	                                	class="btn btn-success rounded-pill btn-block font-weight-bold"
	                                	onclick="event.preventDefault();handleSubmit()">
	                                    {{ __('Confirm Password') }}
	                                </button>

	                            </div>
	                        </div>
	                    </form>
	                </div>
	            </div>

	            <div class="d-flex justify-content-between my-3">
	            	<p class="mb-0 small">
	            		<span class="text-muted">Logged in as:</span> {{request()->user()->username}}
	            	</p>

	            	<form action="/logout" method="post">
	            		@csrf
	            		<button type="submit" class="btn btn-link p-0 btn-sm text-white font-weight-bold">Logout</button>
	            	</form>
	            </div>
	        </div>
	    </div>
	</div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
	function handleSubmit() {
		let warning = document.querySelector('.invalid-feedback');
		if(warning) {
			warning.style.display = 'none';
		}

		let email = document.getElementById('password');
		email.setAttribute('readonly', 'readonly');
		email.style.opacity = '20%';

		let trustedDevice = document.getElementById('trusted-device');
		trustedDevice.style.opacity = '20%';

		let btn = document.getElementById('sbtn');
		btn.classList.add('disabled');
		btn.setAttribute('disabled', 'disabled');
		btn.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="sr-only">Loading...</span></div>';
		document.getElementById('sudoForm').submit()
	}
</script>
@endpush
