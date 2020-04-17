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
    <style type="text/css">
        .feature-circle {
            display: flex !important;
            -webkit-box-pack: center !important;
            justify-content: center !important;
            -webkit-box-align: center !important;
            align-items: center !important;
            margin-right: 1rem !important;
            background-color: #08d !important;
            color: #fff;
            border-radius: 50% !important;
            width: 60px;
            height:60px;
        }
        .section-spacer {
            height: 13vh;
        }
    </style>
</head>
<body class="">
    <main id="content">
        <section class="container">
            <div class="section-spacer"></div>
            <div class="row pt-md-5 mt-5">
                <div class="col-12 col-md-6 d-none d-md-block">
                    <div class="m-my-4">
                        <p class="display-2 font-weight-bold">Photo Sharing</p>
                        <p class="h1 font-weight-bold">For Everyone.</p>
                    </div>
                </div>
                <div class="col-12 col-md-5 offset-md-1">
                    <div>
                        <div class="pt-md-3 d-flex justify-content-center align-items-center">
                            <img src="/img/pixelfed-icon-color.svg" loading="lazy" width="50px" height="50px">
                            <span class="font-weight-bold h3 ml-2 pt-2">Pixelfed</span>
                        </div>
                        <div class="d-block d-md-none">
                            <p class="font-weight-bold mb-0 text-center">Photo Sharing. For Everyone</p>
                        </div>
                        <div class="card my-4 shadow-none border">
                            <div class="card-body px-lg-5">
                                <div class="text-center">
                                    <p class="small text-uppercase font-weight-bold text-muted">Account Login</p>
                                </div>
                                <div>
                                    <form class="px-1" method="POST" action="{{ route('login') }}" id="login_form">
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
                                                        <span class="font-weight-bold small ml-1 text-muted">
                                                            {{ __('Remember Me') }}
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group row mb-0">
                                            <div class="col-md-12">
                                                <button type="submit" class="btn btn-primary btn-block py-0 font-weight-bold text-uppercase">
                                                    {{ __('Login') }}
                                                </button>

                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="card shadow-none border card-body">
                            <p class="text-center mb-0 font-weight-bold small">
                                <a href="/register">Register</a>
                                <span class="px-1">·</span>
                                <a href="/password/reset">Password Reset</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="section-spacer"></div>
            <div class="row py-5 mt-5 mb-5">
                <div class="col-12 col-md-6 d-none d-md-block">
                    <div>
                        <div class="row mt-4 mb-1">
                            <div class="col-4 mt-2 px-0">
                                <div class="px-1 shadow-none">
                                    <img src="/_landing/1.jpeg" class="img-fluid" loading="lazy" width="640px" height="640px">
                                </div>
                            </div>
                            <div class="col-4 mt-2 px-0">
                                <div class="px-1 shadow-none">
                                    <img src="/_landing/2.jpeg" class="img-fluid" loading="lazy" width="640px" height="640px">
                                </div>
                            </div>
                            <div class="col-4 mt-2 px-0">
                                <div class="px-1 shadow-none">
                                    <img src="/_landing/3.jpeg" class="img-fluid" loading="lazy" width="640px" height="640px">
                                </div>
                            </div>
                            <div class="col-4 mt-2 px-0">
                                <div class="px-1 shadow-none">
                                    <img src="/_landing/4.jpeg" class="img-fluid" loading="lazy" width="640px" height="640px">
                                </div>
                            </div>
                            <div class="col-4 mt-2 px-0">
                                <div class="px-1 shadow-none">
                                    <img src="/_landing/5.jpeg" class="img-fluid" loading="lazy" width="640px" height="640px">
                                </div>
                            </div>
                            <div class="col-4 mt-2 px-0">
                                <div class="px-1 shadow-none">
                                    <img src="/_landing/6.jpeg" class="img-fluid" loading="lazy" width="640px" height="640px">
                                </div>
                            </div>
                            <div class="col-4 mt-2 px-0">
                                <div class="px-1 shadow-none">
                                    <img src="/_landing/7.jpeg" class="img-fluid" loading="lazy" width="640px" height="640px">
                                </div>
                            </div>
                            <div class="col-4 mt-2 px-0">
                                <div class="px-1 shadow-none">
                                    <img src="/_landing/8.jpeg" class="img-fluid" loading="lazy" width="640px" height="640px">
                                </div>
                            </div>
                            <div class="col-4 mt-2 px-0">
                                <div class="px-1 shadow-none">
                                    <img src="/_landing/9.jpeg" class="img-fluid" loading="lazy" width="640px" height="640px">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-5 offset-md-1">
                    <div class="section-spacer"></div>
                    <div class="mt-5">
                        <p class="text-center h1 font-weight-bold">Simple. Powerful.</p>
                    </div>
                    <div class="mt-5">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-center">
                                <span class="font-weight-bold h1">{{$data['stats']['posts']}}</span>
                                <span class="d-block text-muted text-uppercase">Posts</span>
                            </span>
                            <span class="text-center">
                                <span class="font-weight-bold h1">{{$data['stats']['likes']}}</span>
                                <span class="d-block text-muted text-uppercase">Likes</span>
                            </span>
                            <span class="text-center">
                                <span class="font-weight-bold h1">{{$data['stats']['hashtags']}}</span>
                                <span class="d-block text-muted text-uppercase">Hashtags Used</span>
                            </span>
                        </div>
                    </div>
                    <div class="mt-5">
                        <p class="lead text-muted text-center">A free and ethical photo sharing platform.</p>
                    </div>
                </div>
            </div>
            <div class="row py-5 mb-5">
                <div class="col-12 col-md-8 offset-md-2">
                    <div class="section-spacer"></div>
                    <div class="mt-5">
                        <p class="text-center display-4 font-weight-bold">Feature Packed.</p>
                    </div>
                    <div class="my-2">
                        <p class="h4 font-weight-light text-muted text-center">The best for the brightest.</p>
                    </div>
                </div>
            </div>
            <div class="row pb-5 mb-5">
                <div class="col-12 col-md-5 offset-md-1">
                    <div class="mb-5">
                        <div class="media">
                            <div class="feature-circle">
                                <i class="far fa-images fa-lg"></i>
                            </div>
                            <div class="media-body">
                                <p class="h5 font-weight-bold mt-2 mb-0">Albums</p>
                                Create an album with up to <span class="font-weight-bold">{{config('pixelfed.max_album_length')}}</span> photos
                            </div>
                        </div>
                    </div>
                    <div class="mb-5">
                        <div class="media">
                            <div class="feature-circle">
                                <i class="far fa-folder fa-lg"></i>
                            </div>
                            <div class="media-body">
                                <p class="h5 font-weight-bold mt-2 mb-0">Collections</p>
                                Organize your posts
                            </div>
                        </div>
                    </div>
                    <div class="mb-5">
                        <div class="media">
                            <div class="feature-circle">
                                <i class="fas fa-image fa-lg"></i>
                            </div>
                            <div class="media-body">
                                <p class="h5 font-weight-bold mt-2 mb-0">Filters</p>
                                Add a filter to your photos
                            </div>
                        </div>
                    </div>
                    
                </div>
                <div class="col-12 col-md-5 offset-md-1">
                    <div class="mb-5">
                        <div class="media">
                            <div class="feature-circle">
                                <i class="far fa-comment fa-lg"></i>
                            </div>
                            <div class="media-body">
                                <p class="h5 font-weight-bold mt-2 mb-0">Comments</p>
                                Comment on a post, or send a reply
                            </div>
                        </div>
                    </div>
                    <div class="mb-5">
                        <div class="media">
                            <div class="feature-circle">
                                <i class="far fa-list-alt fa-lg"></i>
                            </div>
                            <div class="media-body">
                                <p class="h5 font-weight-bold mt-2 mb-0">Discover</p>
                                Explore categories, hashtags and topics
                            </div>
                        </div>
                    </div>
                    @if(config('instance.stories.enabled'))
                    <div class="mb-5">
                        <div class="media">
                            <div class="feature-circle">
                                <i class="fas fa-history fa-lg"></i>
                            </div>
                            <div class="media-body">
                                <p class="h5 font-weight-bold mt-2 mb-0">Stories</p>
                                Share posts that disappear after 24h
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </section>
    </main>
    @include('layouts.partial.footer')
</body>
</html>
