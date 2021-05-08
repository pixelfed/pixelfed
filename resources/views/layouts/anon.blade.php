<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="robots" content="noimageindex, noarchive">
    <meta name="mobile-web-app-capable" content="yes">

    <title>{{ $title ?? config_cache('app.name') }}</title>

    @if(isset($title))<meta property="og:site_name" content="{{ config_cache('app.name') }}">
    <meta property="og:title" content="{{ $title ?? config_cache('app.name') }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{request()->url()}}">
    @endif

    @stack('meta')

    <meta name="medium" content="image">
    <meta name="theme-color" content="#10c5f8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="shortcut icon" type="image/png" href="/img/favicon.png?v=2">
    <link rel="apple-touch-icon" type="image/png" href="/img/favicon.png?v=2">
    <link rel="canonical" href="{{request()->url()}}">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet" data-stylesheet="light">
    @stack('styles')

    <script type="text/javascript">window.App = {}; window.App.config = {!!App\Util\Site\Config::json()!!}</script>
</head>
<body class="">
    @include('layouts.partial.noauthnav')
    <main id="content">
        @yield('content')
    </main>
    @include('layouts.partial.footer')
    <script type="text/javascript" src="{{ mix('js/manifest.js') }}"></script>
    <script type="text/javascript" src="{{ mix('js/vendor.js') }}"></script>
    <script type="text/javascript" src="{{ mix('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
