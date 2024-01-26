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
                    <h1 class="pt-4 pb-1">Forgot Email</h1>
                    <p class="font-weight-light pb-2">Recover your account by sending an email to an associated username</p>
                </div>

                @if(session('status'))
                    <div class="alert alert-success">
                        <div class="d-flex align-items-center font-weight-bold" style="gap:1rem;">
                            <i class="far fa-check-circle fa-lg" style="opacity:70%"></i>

                            {{ session('status') }}
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    @foreach ($errors->all() as $error)
                    <div class="alert alert-danger bg-danger text-white border-danger">
                        <div class="d-flex align-items-center font-weight-bold" style="gap:1rem;">
                            <i class="far fa-exclamation-triangle fa-2x" style="opacity:70%"></i>
                            {{ $error }}
                        </div>
                    </div>
                    @endforeach
                @endif

                <div class="card bg-glass">
                    <div class="card-header bg-transparent p-3 text-center font-weight-bold" style="border-bottom:1px solid #ffffff20">{{ __('Recover Email') }}</div>

                    <div class="card-body">

                        <form id="passwordReset" method="POST" action="{{ route('email.forgot') }}">
                            @csrf

                            <div class="form-group row">
                                <div class="col-md-12">
                                    <label class="font-weight-bold small text-muted">Username</label>
                                    <input
                                        id="username"
                                        type="text"
                                        class="form-control form-control-lg bg-glass text-white"
                                        name="username"
                                        maxlength="15"
                                        placeholder="{{ __('Your username') }}" required>
                                     @if ($errors->has('username') )
                                        <span class="text-danger small mb-3">
                                            <strong>{{ $errors->first('username') }}</strong>
                                        </span>
                                     @endif
                                </div>
                            </div>

                            @if(config('captcha.enabled') || config('captcha.active.login') || config('captcha.active.register'))
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
                                        {{ __('Send Email Reminder') }}
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

                    <a href="{{ route('password.request') }}" class="text-white font-weight-bold text-decoration-none">Forgot password?</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
    function handleSubmit() {
        let username = document.getElementById('username');
        username.classList.add('disabled');

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
