@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="card shadow-none border">
                <div class="card-header bg-transparent p-3">
                    <h4 class="font-weight-bold mb-0 text-center">
                        Account Login
                    </h4>
                </div>

                @if ($errors->any())
                    @foreach ($errors->all() as $error)
                    <div class="alert alert-danger m-3">
                        <span class="font-weight-bold small"><i class="far fa-exclamation-triangle mr-2"></i> {{ $error }}</span>
                    </div>
                    @endforeach
                @endif
                <div class="card-body">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="form-group row mb-0">

                            <div class="col-md-12">
                                <label for="email" class="small font-weight-bold text-muted mb-0">Email Address</label>
                                <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" placeholder="{{__('Email')}}" required autofocus>

                                @if ($errors->has('email'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif

                                <div class="help-text small text-right mb-0">
                                    <a href="{{ route('email.forgot') }}" class="small text-muted font-weight-bold">
                                        {{ __('Forgot Email') }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row mb-0">

                            <div class="col-md-12">
                                <label for="password" class="small font-weight-bold text-muted mb-0">Password</label>
                                <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" placeholder="{{__('Password')}}" required>

                                @if ($errors->has('password'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif

                                <p class="help-text small text-right mb-0">
                                    <a href="{{ route('password.request') }}" class="small text-muted font-weight-bold">
                                        {{ __('Forgot Password') }}
                                    </a>
                                </p>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                        <span class="font-weight-bold ml-1 text-muted">
                                            {{ __('Remember Me') }}
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        @if(
                        	config('captcha.enabled') ||
                        	config('captcha.active.login') ||
                        	(
                        		config('captcha.triggers.login.enabled') &&
                        		request()->session()->has('login_attempts') &&
                        		request()->session()->get('login_attempts') >= config('captcha.triggers.login.attempts')
                        	)
                        )
	                        <div class="d-flex justify-content-center mb-3">
	                            {!! Captcha::display() !!}
	                        </div>
                        @endif

                        <button type="submit" class="btn btn-primary btn-block btn-lg font-weight-bold rounded-pill">
                            {{ __('Login') }}
                        </button>

                    </form>
                    @if(
                        (config_cache('pixelfed.open_registration') && config('remote-auth.mastodon.enabled')) ||
                        (config('remote-auth.mastodon.ignore_closed_state') && config('remote-auth.mastodon.enabled'))
                    )
                    <hr>
                    <form method="POST" action="/auth/raw/mastodon/start">
                        @csrf
                        <div class="form-group row mb-0">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary btn-sm btn-block rounded-pill font-weight-bold" style="background: linear-gradient(#6364FF, #563ACC);">
                                    Sign-in with Mastodon
                                </button>
                            </div>
                        </div>
                    </form>
                    @endif

                    @if(config_cache('pixelfed.open_registration'))
                    <hr>

                    <p class="text-center font-weight-bold mb-0">
                        <a href="/register">Register</a>
                    </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function() {
    function getQueryParam(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    }
    const email = getQueryParam('email');
    if (email) {
        const emailInput = document.getElementById('email');
        if (emailInput) {
            emailInput.value = email;
            const passwordInput = document.getElementById('password');
            if (passwordInput) {
                passwordInput.focus();
            }
        }
    }
});
</script>
@endpush
