@extends('layouts.blank')

@push('styles')
<link href="{{ mix('css/landing.css') }}" rel="stylesheet">
<link rel="preload" as="image" href="{{ url('/_landing/bg.jpg')}}" />
@endpush

@section('content')
<div class="page-wrapper">
	<div class="container mt-4">
	    <div class="row justify-content-center">
	        <div class="col-lg-5">
	        	<div class="text-center">
	                <a href="/">
	                	<img src="/img/pixelfed-icon-white.svg" height="60px">
	            	</a>
	                <h1 class="pt-4 pb-1">Reset Password</h1>
	                <p class="font-weight-light pb-2">Send a password reset mail to reset your password</p>
	            </div>

                @if(session('status') || $errors->has('email'))
                    <div class="alert alert-info small">
                    	<div class="d-flex align-items-center font-weight-bold" style="gap:0.5rem;">
                    		<i class="far fa-exclamation-triangle fa-lg" style="opacity:20%"></i>

                        	{{ session('status') ?? $errors->first('email') }}
                        </div>
                    </div>
                @endif

	            <div class="card bg-glass">
	                <div class="card-header bg-transparent p-3 text-center font-weight-bold" style="border-bottom:1px solid #ffffff20">{{ __('Reset Password') }}</div>

	                <div class="card-body">

	                    <form id="passwordReset" method="POST" action="{{ route('password.email') }}">
	                        @csrf

	                        <div class="form-group row">
	                            <div class="col-md-12">
	                            	<label class="font-weight-bold small text-muted">Email</label>
	                                <input id="email" type="email" class="form-control" name="email" placeholder="{{ __('E-Mail Address') }}" required>
	                                 @if ($errors->has('email') && $errors->first('email') === 'The email must be a valid email address.')
	                                    <span class="text-danger small mb-3">
	                                        <strong>{{ $errors->first('email') }}</strong>
	                                    </span>
	                                 @endif
	                            </div>
	                        </div>

							@if(config('captcha.enabled'))
							<label class="font-weight-bold small text-muted">Captcha</label>
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
	                                <button type="button" id="sbtn" class="btn btn-primary btn-block rounded-pill font-weight-bold" onclick="event.preventDefault();handleSubmit()">
	                                    {{ __('Send Password Reset Link') }}
	                                </button>
	                            </div>
	                        </div>
	                    </form>
	                </div>
	            </div>

	            <div class="mt-3 d-flex justify-content-between align-items-center">
                    <a class="btn btn-link text-white font-weight-bold text-decoration-none" href="{{ route('login') }}">
                        <i class="far fa-long-arrow-left fa-lg mr-1"></i> {{ __('Back to Login') }}
                    </a>

                    <a href="#" class="text-white font-weight-bold text-decoration-none" onclick="event.preventDefault();forgotUsername()">Forgot email?</a>
	            </div>
	        </div>
	    </div>
	</div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
	function forgotUsername() {
		swal({
			title: 'Forgot email?',
			text: 'Contact the instance admins to assist you in recovering your account.',
			icon: 'info',
			buttons: {
				contact: {
					text: "Contact Admins",
					value: "contact",
					className: "bg-danger"
				},
				cancel: "Close",
			},
		})
		.then((value) => {
			switch(value) {
				case 'contact':
					window.location.href = '/site/contact';
				break;
			}
		});
	}

	function handleSubmit() {
		let email = document.getElementById('email');
		email.classList.add('disabled');

		let btn = document.getElementById('sbtn');
		btn.classList.add('disabled');
		btn.setAttribute('disabled', 'disabled');
		btn.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="sr-only">Loading...</span></div>';
		document.getElementById('passwordReset').submit()
	}
</script>
@endpush
