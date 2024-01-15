@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="card shadow-none border mb-3">
                <a
                    class="card-body d-flex flex-column justify-content-center align-items-center text-decoration-none"
                    href="{{ $pc->parent->url() }}"
                    target="_blank">
                    <p class="text-center font-weight-bold text-muted">You've been invited by:</p>

                    <div class="media align-items-center">
                        <img
                            src="{{ $pc->parent->avatarUrl() }}"
                            width="30"
                            height="30"
                            class="rounded-circle mr-2"
                            draggable="false"
                            onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';">

                        <div class="media-body">
                            <p class="lead font-weight-bold mb-0 text-dark" style="line-height: 1;">&commat;{{ $pc->parent->username }}</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="card shadow-none border">
                <div class="card-header bg-white p-3 text-center font-weight-bold">Create your Account</div>

                <div class="card-body">
                    <form method="POST" class="px-md-3">
                        @csrf

                        <input type="hidden" name="rt" value="{{ (new \App\Http\Controllers\Auth\RegisterController())->getRegisterToken() }}">
                        <div class="form-group row">
                            <div class="col-md-12">
                                <label class="small font-weight-bold text-lighter">Name</label>
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
                                <label class="small font-weight-bold text-lighter">Username</label>
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
                                <label class="small font-weight-bold text-lighter">Password</label>
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
                                <label class="small font-weight-bold text-lighter">Confirm Password</label>
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" placeholder="{{ __('Confirm Password') }}" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-12">
                                <div class="form-check">
                                  <input class="form-check-input" name="agecheck" type="checkbox" value="true" id="ageCheck" required>
                                  <label class="form-check-label" for="ageCheck">
                                    I am at least 16 years old
                                  </label>
                                </div>
                            </div>
                        </div>

                        @if(config('captcha.enabled') || config('captcha.active.register'))
                        <div class="d-flex justify-content-center my-3">
                            {!! Captcha::display() !!}
                        </div>
                        @endif

                        <p class="small">By signing up, you agree to our <a href="{{route('site.terms')}}" class="font-weight-bold text-dark">Terms of Use</a> and <a href="{{route('site.privacy')}}" class="font-weight-bold text-dark">Privacy Policy</a>, in addition, you understand that your account is managed by <span class="font-weight-bold">{{ $pc->parent->username }}</span> and they can limit your account without your permission. For more details, view the <a href="/site/kb/parental-controls" class="text-dark font-weight-bold">Parental Controls</a> help center page.</p>

                        <div class="form-group row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary btn-block py-0 font-weight-bold">
                                    {{ __('Register') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
