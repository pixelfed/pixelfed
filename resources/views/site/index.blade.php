<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="mobile-web-app-capable" content="yes">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <meta property="og:site_name" content="{{ config('app.name', 'pixelfed') }}">
    <meta property="og:title" content="{{ config('app.name', 'pixelfed') }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{request()->url()}}">
    <meta property="og:description" content="Federated Image Sharing">

    <meta name="medium" content="image">
    <meta name="theme-color" content="#10c5f8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="shortcut icon" type="image/png" href="/img/favicon.png?v=2">
    <link rel="apple-touch-icon" type="image/png" href="/img/favicon.png?v=2">
    <link href="{{ mix('css/landing.css') }}" rel="stylesheet">
    <script type="text/javascript">window.App = {}; window.App.config = {!!App\Util\Site\Config::json()!!}</script>
</head>
<body class="">
    <main id="content">
        <section class="container">
            <div class="row py-5 mb-5">
                <div class="col-12 col-md-6 d-none d-md-block">
                    <div class="m-md-4" style="position: absolute; transform: scale(0.66)">
                        <div class="marvel-device note8" style="position: absolute;z-index:10;">
                            <div class="inner"></div>
                            <div class="overflow">
                                <div class="shadow"></div>
                            </div>
                            <div class="speaker"></div>
                            <div class="sensors"></div>
                            <div class="more-sensors"></div>
                            <div class="sleep"></div>
                            <div class="volume"></div>
                            <div class="camera"></div>
                            <div class="screen">
                                <img src="/img/landing/android_1.jpg" class="img-fluid" loading="lazy">
                            </div>
                        </div>
                        <div class="marvel-device iphone-x" style="position: absolute;z-index: 20;margin: 99px 0 0 151px;">
                            <div class="notch">
                                <div class="camera"></div>
                                <div class="speaker"></div>
                            </div>
                            <div class="top-bar"></div>
                            <div class="sleep"></div>
                            <div class="bottom-bar"></div>
                            <div class="volume"></div>
                            <div class="overflow">
                                <div class="shadow shadow--tr"></div>
                                <div class="shadow shadow--tl"></div>
                                <div class="shadow shadow--br"></div>
                                <div class="shadow shadow--bl"></div>
                            </div>
                            <div class="inner-shadow"></div>
                            <div class="screen">
                                <div id="iosDevice">
                                    <img src="/img/landing/ios_4.jpg" class="img-fluid" loading="lazy">
                                    <img src="/img/landing/ios_3.jpg" class="img-fluid" loading="lazy">
                                    <img src="/img/landing/ios_2.jpg" class="img-fluid" loading="lazy">
                                    <img src="/img/landing/ios_1.jpg" class="img-fluid" loading="lazy">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-5 offset-md-1">
                    <div>
                        <div class="card my-4 shadow-none border">
                            <div class="card-body px-lg-5">
                                <div class="text-center pt-3">
                                    <img src="/img/pixelfed-icon-color.svg">
                                </div>
                                <div class="py-3 text-center">
                                    <h3 class="font-weight-bold">Pixelfed</h3>
                                    <p class="mb-0 lead">Photo sharing for everyone</p>
                                </div>
                                <div>
                                    @if(true === config('pixelfed.open_registration'))
                                    <form class="px-1" method="POST" action="{{ route('register') }}" id="register_form">
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
                                                <input id="username" type="text" class="form-control{{ $errors->has('username') ? ' is-invalid' : '' }}" name="username" value="{{ old('username') }}" placeholder="{{ __('Username') }}" required maxlength="15" minlength="2">

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
                                        <div class="form-group row">
                                            <div class="col-md-12">
                                                <button type="submit" class="btn btn-primary btn-block py-0 font-weight-bold">
                                                    {{ __('Register') }}
                                                </button>
                                            </div>
                                        </div>
                                        <p class="mb-0 font-weight-bold text-lighter small">By signing up, you agree to our <a href="{{route('site.terms')}}" class="text-muted">Terms of Use</a> and <a href="{{route('site.privacy')}}" class="text-muted">Privacy Policy</a>.</p>
                                    </form>
                                    @else
                                    <div style="min-height: 350px" class="d-flex justify-content-center align-items-center">
                                        <div class="text-center">
                                            <p class="lead">Registrations are closed.</p>
                                            <p class="text-lighter small">You can find a list of other instances on <a href="https://the-federation.info/pixelfed" class="text-muted font-weight-bold">the-federation.info/pixelfed</a> or <a href="https://fediverse.network/pixelfed" class="text-muted font-weight-bold">fediverse.network/pixelfed</a></p>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="card shadow-none border card-body">
                            <p class="text-center mb-0 font-weight-bold">Have an account? <a href="/login">Log in</a></p>
                        </div>
                    </div>
                </div>
            </div>

        </section>
    </main>
    @include('layouts.partial.footer')
</body>
</html>
