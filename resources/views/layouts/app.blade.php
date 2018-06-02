<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="robots" content="noimageindex, noarchive">
    <meta name="mobile-web-app-capable" content="yes">

    <title>{{ $title or config('app.name', 'Laravel') }}</title>
    <meta property="og:site_name" content="{{ config('app.name', 'Laravel') }}">
    <meta property="og:title" content="{{ $title or config('app.name', 'Laravel') }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{request()->url()}}">

    @stack('meta')

    <meta name="medium" content="image">
    <meta name="theme-color" content="#10c5f8">

    <link rel="canonical" href="{{request()->url()}}">
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/simple-line-icons/2.4.1/css/simple-line-icons.min.css" integrity="sha256-7O1DfUu4pybYI7uAATw34eDrgQaWGOfMV/8erfDQz/Q=" crossorigin="anonymous" />
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="">
    @include('layouts.partial.nav')
    <main class="">
        @yield('content')
    </main>
    <div class="align-items-end">
        @include('layouts.partial.footer')
    </div>
    <script type="text/javascript" src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
