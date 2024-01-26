@extends('layouts.blank')

@push('styles')
<link href="{{ mix('css/landing.css') }}" rel="stylesheet">
<link rel="preload" as="image" href="{{ url('/_landing/bg.jpg')}}" />
@endpush

@section('content')
<div class="page-wrapper">
	<div class="container mt-4">
	    <div class="row justify-content-center">
	        <div class="col-xl-6 col-lg-5 col-md-7 col-12">
	        	<div class="text-center">
	                <a href="/">
	                	<img src="/img/pixelfed-icon-white.svg" height="60px">
	            	</a>
	                <h1 class="pt-4 pb-1">Reset Password</h1>
	                <p class="font-weight-light pb-2">Use this form to reset your password.</p>
	            </div>

				@if ($errors)
					@foreach($errors as $error)
                    <span class="invalid-feedback">
                        <strong>{{ $error }}</strong>
                    </span>
                    @endforeach
                @endif

	            <div class="card bg-glass">
	                <div class="card-header bg-transparent p-3 text-center font-weight-bold" style="border-bottom:1px solid #ffffff20">{{ __('Reset Password') }}</div>

	                <div class="card-body">
	                    <form id="passwordReset" method="POST" action="{{ route('password.request') }}">
	                        @csrf

	                        <input type="hidden" name="token" value="{{ $token }}">
	                        <input type="hidden" name="email" value="{{ $email ?? old('email') }}">

	                        <div class="form-group row">
	                            <div class="col-md-12">
	                            	<label class="font-weight-bold small text-muted">Email</label>
	                                <input
	                                	id="email"
	                                	type="text"
                                        class="form-control form-control-lg bg-dark bg-glass text-white{{ $errors->has('email') ? ' is-invalid' : '' }}"
	                                	name="email"
	                                	value="{{ $email ?? old('email') }}"
	                                	placeholder="{{ __('E-Mail Address') }}"
	                                	required
	                                	disabled
                                        style="opacity:.5">

	                                @if ($errors->has('email'))
	                                    <span class="invalid-feedback">
	                                        <strong>{{ $errors->first('email') }}</strong>
	                                    </span>
	                                @endif
	                            </div>
	                        </div>

	                        <hr class="bg-muted">

	                        <div class="form-group row">
	                            <div class="col-md-12">
	                            	<label class="font-weight-bold small text-muted">New Password</label>

	                                <input
	                                	id="password"
	                                	type="password"
                                        class="form-control form-control-lg bg-glass text-white{{ $errors->has('password') ? ' is-invalid' : '' }}"
	                                	name="password"
	                                	placeholder="{{ __('Password') }}"
	                                	minlength="{{config('pixelfed.min_password_length')}}"
	                                	maxlength="72"
	                                	autocomplete="new-password"
	                                	autofocus
	                                	required>

	                                @if ($errors->has('password'))
	                                    <span class="invalid-feedback">
	                                        <strong>{{ $errors->first('password') }}</strong>
	                                    </span>
	                                @else
	                                	<p class="help-text small text-muted mb-0 mt-1">Enter a new password between {{config('pixelfed.min_password_length')}}-72 characters long.</p>
	                                @endif
	                            </div>
	                        </div>

	                        <div class="form-group row">
	                            <div class="col-md-12">
	                            	<label class="font-weight-bold small text-muted">Confirm New Password</label>

	                                <input
	                                	id="password-confirm"
	                                	type="password"
                                        class="form-control form-control-lg bg-glass text-white{{ $errors->has('password_confirmation') ? ' is-invalid' : '' }}"
	                                	name="password_confirmation"
	                                	placeholder="{{ __('Confirm Password') }}"
	                                	minlength="{{config('pixelfed.min_password_length')}}"
	                                	autocomplete="new-password"
	                                	maxlength="72"
	                                	required>

	                                @if ($errors->has('password_confirmation'))
	                                    <span class="invalid-feedback">
	                                        <strong>{{ $errors->first('password_confirmation') }}</strong>
	                                    </span>
	                                @endif
	                            </div>
	                        </div>

							@if(config('captcha.enabled'))
							<label class="font-weight-bold small pt-3 text-muted">Captcha</label>
	                        <div class="d-flex flex-grow-1">
	                            {!! Captcha::display(['data-theme' => 'dark']) !!}
	                        </div>
	                        @if ($errors->has('h-captcha-response'))
                                <div class="text-danger small mb-3">
                                    <strong>{{ $errors->first('h-captcha-response') }}</strong>
                                </div>
                            @endif
	                        @endif

	                        <div class="form-group row pt-4 mb-0">
	                            <div class="col-md-12">
	                                <button
	                                	type="button"
	                                	id="sbtn"
	                                	class="btn btn-success btn-block rounded-pill font-weight-bold"
	                                	onclick="event.preventDefault();handleSubmit()">
	                                    {{ __('Reset Password') }}
	                                </button>
	                            </div>
	                        </div>
	                    </form>
	                </div>
	            </div>
	        </div>
	    </div>
	</div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
	function handleSubmit() {
		let btn = document.getElementById('sbtn');
		btn.classList.add('disabled');
		btn.setAttribute('disabled', 'disabled');
		btn.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="sr-only">Loading...</span></div>';
		document.getElementById('passwordReset').submit()
	}
</script>
@endpush

@push('styles')
<style>
    .bg-glass:focus {
        background: rgba(255, 255, 255, 0.05) !important;
        box-shadow: none !important;
        border-color: rgba(255, 255, 255, 0.3);
    }
</style>
@endpush
