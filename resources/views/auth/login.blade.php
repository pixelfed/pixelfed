@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="">
                <div class="card-header bg-transparent p-3 text-center font-weight-bold h3">{{ __('Login') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="form-group row">

                            <div class="col-md-12">
                                <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" placeholder="{{__('Email')}}" required autofocus>

                                @if ($errors->has('email'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">

                            <div class="col-md-12">
                                <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" placeholder="{{__('Password')}}" required>

                                @if ($errors->has('password'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
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

                        <div class="form-group row mb-4">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary btn-block btn-lg font-weight-bold">
                                    {{ __('Login') }}
                                </button>

                            </div>
                        </div>

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

                    <hr>

                    <p class="text-center font-weight-bold">
                        @if(config_cache('pixelfed.open_registration'))
                        <a href="/register">Register</a>
                        <span class="px-1">·</span>
                        @endif
                        <a href="{{ route('password.request') }}">
                            {{ __('Forgot Password') }}
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
