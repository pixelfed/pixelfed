@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="card shadow-none border">
                <div class="card-header bg-transparent p-3 text-center">
                    <h4 class="font-weight-bold mb-0">
                        @if($step === '2fa')
                        {{ __('Two-factor authentication') }}
                        @elseif($step === 'verify')
                        {{ __('Verify your email') }}
                        @else
                        {{ __('auth.loginTitle') }}
                        @endif
                    </h4>
                    @if($returnTo)
                    <p class="small text-muted mb-0 mt-2">
                        {{ __('Continue to') }} <span class="font-weight-bold">{{ $returnTo }}</span>
                    </p>
                    @endif
                </div>

                @if (session('status'))
                <div class="alert alert-success m-3">
                    <span class="font-weight-bold small"><i class="far fa-check-circle mr-2"></i> {{ session('status') }}</span>
                </div>
                @endif

                @if($step === 'credentials')
                @foreach ($errors->all() as $error)
                <div class="alert alert-danger m-3">
                    <span class="font-weight-bold small"><i class="far fa-exclamation-triangle mr-2"></i> {{ $error }}</span>
                </div>
                @endforeach
                @elseif($step === 'verify' && $errors->has('verify'))
                <div class="alert alert-danger m-3">
                    <span class="font-weight-bold small"><i class="far fa-exclamation-triangle mr-2"></i> {{ $errors->first('verify') }}</span>
                </div>
                @endif

                <div class="card-body">
                    @if($step === '2fa')

                    @php($backup = ($mode ?? 'totp') === 'backup')

                    <p class="text-center text-muted small mb-3">
                        @if($backup)
                        {{ __('Enter one of your backup codes.') }}
                        @else
                        {{ __('Enter the 6-digit code from your authenticator app.') }}
                        @endif
                    </p>

                    <form method="POST" action="{{ route('login.2fa') }}" id="twoFactorForm">
                        @csrf
                        <input type="hidden" name="mode" value="{{ $backup ? 'backup' : 'totp' }}">

                        <div class="form-group">
                            @if($backup)
                            <input
                                id="code"
                                type="text"
                                name="code"
                                data-mode="backup"
                                class="form-control form-control-lg text-center{{ $errors->has('code') ? ' is-invalid' : '' }}"
                                autocomplete="off"
                                autocapitalize="off"
                                spellcheck="false"
                                maxlength="32"
                                placeholder="{{ __('Backup code') }}"
                                required
                                autofocus>
                            @else
                            <input
                                id="code"
                                type="text"
                                name="code"
                                data-mode="totp"
                                class="form-control form-control-lg text-center{{ $errors->has('code') ? ' is-invalid' : '' }}"
                                style="font-size:2rem;letter-spacing:.45em;text-indent:.45em;font-variant-numeric:tabular-nums"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                pattern="[0-9]*"
                                maxlength="6"
                                placeholder="000000"
                                required
                                autofocus>
                            @endif

                            @if ($errors->has('code'))
                            <span class="invalid-feedback d-block text-center">
                                <strong>{{ $errors->first('code') }}</strong>
                            </span>
                            @endif
                        </div>

                        <button type="submit" id="twoFactorSubmit" class="btn btn-primary btn-block btn-lg font-weight-bold rounded-pill">
                            {{ __('Verify') }}
                        </button>
                    </form>

                    <p class="text-center mt-3 mb-0">
                        @if($backup)
                        <a href="{{ route('login', ['step' => '2fa']) }}" class="small text-muted font-weight-bold">{{ __('Use your authenticator app instead') }}</a>
                        @else
                        <a href="{{ route('login', ['step' => '2fa', 'mode' => 'backup']) }}" class="small text-muted font-weight-bold">{{ __('Use a backup code instead') }}</a>
                        @endif
                    </p>

                    @elseif($step === 'verify')

                    @php($showChange = ($changeEmail ?? false) || $errors->has('email'))

                    <p class="text-center text-muted small mb-4">
                        {{ __('We sent a verification link to') }} <span class="font-weight-bold">{{ $pendingUser->email }}</span>. {{ __('Open it to finish signing in.') }}
                    </p>

                    <a href="{{ route('login.verify.continue') }}" class="btn btn-primary btn-block btn-lg font-weight-bold rounded-pill mb-2">
                        {{ __('I have verified my email') }}
                    </a>

                    <form method="POST" action="{{ route('login.verify.resend') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary btn-block font-weight-bold rounded-pill">
                            {{ __('Resend email') }}
                        </button>
                    </form>

                    @if($showChange)
                    <div class="mt-4">
                        <form method="POST" action="{{ route('login.verify.email') }}">
                            @csrf
                            <label for="newEmail" class="small font-weight-bold text-muted mb-0">{{ __('New email address') }}</label>
                            <div class="input-group">
                                <input
                                    id="newEmail"
                                    type="email"
                                    name="email"
                                    class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}"
                                    value="{{ old('email') }}"
                                    autocomplete="email"
                                    required
                                    autofocus>
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary font-weight-bold">{{ __('Update') }}</button>
                                </div>
                                @if ($errors->has('email'))
                                <span class="invalid-feedback">
                                    <strong>{{ $errors->first('email') }}</strong>
                                </span>
                                @endif
                            </div>
                        </form>
                        <p class="text-center mt-2 mb-0">
                            <a href="{{ route('login', ['step' => 'verify']) }}" class="small text-muted font-weight-bold">{{ __('Cancel') }}</a>
                        </p>
                    </div>
                    @else
                    <p class="text-center mt-3 mb-0">
                        <a href="{{ route('login', ['step' => 'verify', 'change' => 1]) }}" class="small text-muted font-weight-bold">{{ __('Wrong email address?') }}</a>
                    </p>
                    @endif

                    @else

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="form-group row mb-0">
                            <div class="col-md-12">
                                <label for="email" class="small font-weight-bold text-muted mb-0">{{ __('auth.emailAddress') }}</label>
                                <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" placeholder="{{ __('Email') }}" required autofocus>

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
                                <label for="password" class="small font-weight-bold text-muted mb-0">{{ __('auth.password') }}</label>
                                <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" placeholder="{{ __('Password') }}" required>

                                @if ($errors->has('password'))
                                <span class="invalid-feedback">
                                    <strong>{{ $errors->first('password') }}</strong>
                                </span>
                                @endif

                                <p class="help-text small text-right mb-0">
                                    <a href="{{ route('password.request') }}" class="small text-muted font-weight-bold">
                                        {{ __('auth.forgot') }}
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
                                            {{ __('auth.remember') }}
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        @if(
                        (bool) config_cache('captcha.enabled') &&
                        (bool) config_cache('captcha.active.login') ||
                        (
                        (bool) config_cache('captcha.triggers.login.enabled') &&
                        request()->session()->has('login_attempts') &&
                        request()->session()->get('login_attempts') >= config('captcha.triggers.login.attempts')
                        )
                        )
                        <div class="d-flex justify-content-center mb-3">
                            {!! Captcha::display() !!}
                        </div>
                        @endif

                        <button type="submit" class="btn btn-primary btn-block btn-lg font-weight-bold rounded-pill">
                            {{ __('auth.login') }}
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
                                    {{ __('auth.signInMastodon') }}
                                </button>
                            </div>
                        </div>
                    </form>
                    @endif

                    @if( config('remote-auth.oidc.enabled') )
                    <hr>
                    <div class="form-group row mb-0">
                        <div class="col-md-12">
                            <a href="/auth/oidc/start" class="btn btn-primary btn-sm btn-block rounded-pill font-weight-bold" style="background: linear-gradient(#6364FF, #563ACC);">
                                Sign-in with OIDC
                            </a>
                        </div>
                    </div>
                    @endif

                    @if((bool) config_cache('pixelfed.open_registration') || (bool) config_cache('instance.curated_registration.enabled'))
                    <hr>
                    <p class="text-center font-weight-bold mb-0">
                        <a href="/register">{{ __('auth.register') }}</a>
                    </p>
                    @endif

                    @endif

                    @if($pendingUser)
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <p class="mb-0 small text-muted">
                            {{ __('Signing in as') }} <span class="font-weight-bold">{{ '@'.$pendingUser->username }}</span>
                        </p>
                        <a href="{{ route('login.cancel') }}" class="small font-weight-bold">{{ __('Use a different account') }}</a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function() {
        let submitting = false;

        function lock() {
            submitting = true;
            const input = document.getElementById('code');
            const btn = document.getElementById('twoFactorSubmit');
            if (input) {
                input.readOnly = true;
            }
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
            }
        }

        document.addEventListener('input', function(e) {
            if (e.target.id !== 'code' || e.target.dataset.mode !== 'totp') {
                return;
            }
            const input = e.target;
            input.value = input.value.replace(/\D/g, '').slice(0, 6);
            if (input.value.length === 6 && !submitting && input.form) {
                lock();
                input.form.submit();
            }
        });

        document.addEventListener('submit', function(e) {
            if (e.target.id !== 'twoFactorForm') {
                return;
            }
            if (submitting) {
                e.preventDefault();
                return;
            }
            lock();
        });

        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            if (!emailInput) {
                return;
            }
            const email = new URLSearchParams(window.location.search).get('email');
            if (email) {
                emailInput.value = email;
                const passwordInput = document.getElementById('password');
                if (passwordInput) {
                    passwordInput.focus();
                }
            }
        });
    })();
</script>
@endpush