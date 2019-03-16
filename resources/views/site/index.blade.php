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
    <link href="{{ mix('css/app.css') }}" rel="stylesheet" data-stylesheet="light">
</head>
<body class="">
    <main id="content">
        <landing-page></landing-page>
    </main>
    <footer>
        <div class="container py-3">
            <p class="mb-0 text-uppercase font-weight-bold small text-justify">
                <a href="{{route('site.about')}}" class="text-primary pr-3">{{__('site.about')}}</a>
                <a href="{{route('site.help')}}" class="text-primary pr-3">{{__('site.help')}}</a>
                <a href="{{route('site.opensource')}}" class="text-primary pr-3">{{__('site.opensource')}}</a>
                <a href="{{route('site.terms')}}" class="text-primary pr-3">{{__('site.terms')}}</a>
                <a href="{{route('site.privacy')}}" class="text-primary pr-3">{{__('site.privacy')}}</a>
                <a href="{{route('site.platform')}}" class="text-primary pr-3">API</a>
                <a href="{{route('site.language')}}" class="text-primary pr-3">{{__('site.language')}}</a>
                <a href="https://pixelfed.org" class="text-muted float-right" rel="noopener" title="version {{config('pixelfed.version')}}" data-toggle="tooltip">Powered by PixelFed</a>
            </p>
        </div>
    </footer>
</body>

<script type="text/javascript" src="{{mix('js/app.js')}}"></script>
<script type="text/javascript" src="{{mix('js/landing.js')}}"></script>
</html>

