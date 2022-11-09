@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header bg-white p-3 text-center font-weight-bold">{{ __('auth.registerTitle') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('register') }}" class="px-md-3">
                        @csrf
                        <input type="hidden" name="rt" value="{{ (new \App\Http\Controllers\Auth\RegisterController())->getRegisterToken() }}">
                        <div class="form-group row">
                            <div class="col-md-12">
                                <label class="small font-weight-bold text-lighter">{{ __('auth.name') }}</label>
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
                                <label class="small font-weight-bold text-lighter">{{ __('auth.username') }}</label>
                                <input id="username" type="text" class="form-control{{ $errors->has('username') ? ' is-invalid' : '' }}" name="username" value="{{ old('username') }}" placeholder="{{ __('auth.username') }}" required>

                                @if ($errors->has('username'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('username') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-12">
                                <label class="small font-weight-bold text-lighter">Email</label>
                                <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" placeholder="{{ __('auth.emailAddress') }}" required>

                                @if ($errors->has('email'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-12">
                                <label class="small font-weight-bold text-lighter">{{ __('auth.password') }}</label>
                                <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" placeholder="{{ __('auth.password') }}" required>

                                @if ($errors->has('password'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-12">
                                <label class="small font-weight-bold text-lighter">{{ __('auth.confirm-password')}}</label>
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" placeholder="{{ __('auth.confirm-password') }}" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-12">
                                <div class="form-check">
                                  <input class="form-check-input" name="agecheck" type="checkbox" value="true" id="ageCheck" required>
                                  <label class="form-check-label" for="ageCheck">
                                  {{ __('auth.age') }}
                                  </label>
                                </div>
                            </div>
                        </div>

                        @if(config('captcha.enabled'))
                        <div class="d-flex justify-content-center my-3">
                            {!! Captcha::display() !!}
                        </div>
                        @endif

                        <p class="small">{!! __('auth.terms') !!}</p>

                        <div class="form-group row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary btn-block py-0 font-weight-bold">
                                    {{ __('auth.register') }}
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
