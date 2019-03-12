<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="mobile-web-app-capable" content="yes">

    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>
    <link rel="manifest" href="/manifest.json">

    <meta property="og:site_name" content="{{ config('app.name', 'pixelfed') }}">
    <meta property="og:title" content="{{ $title ?? config('app.name', 'pixelfed') }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{request()->url()}}">
    @stack('meta')

    <meta name="medium" content="image">
    <meta name="theme-color" content="#10c5f8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="shortcut icon" type="image/png" href="/img/favicon.png?v=2">
    <link rel="canonical" href="{{request()->url()}}">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet" data-stylesheet="light">
    @stack('styles')

</head>
<body class="{{Auth::check()?'loggedIn':''}}">
    @include('layouts.partial.nav')
    <main id="content">
        @yield('content')
    </main>
    @include('layouts.partial.footer')
    <script type="text/javascript" src="{{ mix('js/app.js') }}"></script>
    <script type="text/javascript" src="{{ mix('js/components.js') }}"></script>
    @stack('scripts')
    @if(Auth::check())
    <div class="d-block d-sm-none mt-5"></div>
    <div class="d-block d-sm-none fixed-bottom">
        <div class="card card-body rounded-0 py-2 d-flex align-items-middle box-shadow" style="border-top:1px solid #F1F5F8">
            <ul class="nav nav-pills nav-fill">
              <li class="nav-item">
                <a class="nav-link {{request()->is('/')?'text-primary':'text-muted'}}" href="/"><i class="fas fa-home fa-lg"></i></a>
              </li>
              <li class="nav-item">
                <a class="nav-link {{request()->is('timeline/public')?'text-primary':'text-muted'}}" href="/timeline/public"><i class="far fa-map fa-lg"></i></a>
              </li>
              <li class="nav-item">
                <div class="nav-link text-black cursor-pointer" data-toggle="modal" data-target="#composeModal"><i class="far fa-plus-square fa-lg"></i></div>
              </li>
              <li class="nav-item">
                <a class="nav-link {{request()->is('discover')?'text-primary':'text-muted'}}" href="{{route('discover')}}"><i class="far fa-compass fa-lg"></i></a>
              </li>
              <li class="nav-item">
                <a class="nav-link {{request()->is('account/activity')?'text-primary':'text-muted'}} tooltip-notification" href="/account/activity"><i class="far fa-bell fa-lg"></i></a>
              </li>
            </ul>
        </div>
    </div>
    <div class="modal" tabindex="-1" role="dialog" id="composeModal">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          @include('timeline.partial.new-form')
        </div>
      </div>
    </div>
    @endif
</body>
</html>
