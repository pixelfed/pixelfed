@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-7 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <p class="h4 font-weight-bold pt-5">A Photo Sharing Experience For Everyone</p>
                    <div class="p-5 mb-5">
                        <img src="/img/Macbook__ipad__iphone.svg" width="100%">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-5">
            <div class="card">
                <div class="card-header bg-white p-3 text-center font-weight-bold">{{ __('Register a new account') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('register') }}" class="px-5">
                        @csrf

                        <div class="form-group row">
                            <div class="col-md-12">
                                <input id="name" type="text" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" name="name" value="{{ old('name') }}" placeholder="{{ __('Name') }}" required autofocus>

                                @if ($errors->has('name'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('name') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-12">
                                <input id="username" type="text" class="form-control{{ $errors->has('username') ? ' is-invalid' : '' }}" name="username" value="{{ old('username') }}" placeholder="{{ __('Username') }}" required>

                                @if ($errors->has('username'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('username') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-12">
                                <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" placeholder="{{ __('E-Mail Address') }}" required>

                                @if ($errors->has('email'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-12">
                                <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" placeholder="{{ __('Password') }}" required>

                                @if ($errors->has('password'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-12">
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" placeholder="{{ __('Confirm Password') }}" required>
                            </div>
                        </div>

                        @if(config('pixelfed.recaptcha'))
                        <div class="row my-3">
                            {!! Recaptcha::render() !!}
                        </div>
                        @endif
                        
                        <div class="form-group row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary btn-block py-0 font-weight-bold">
                                    {{ __('Register') }}
                                </button>
                            </div>
                        </div>
                        <p class="mb-0 font-weight-bold text-muted">By signing up, you agree to our <a href="#">Terms</a>, <a href="#">Data Policy</a> and <a href="#">Cookies Policy</a>.</p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
