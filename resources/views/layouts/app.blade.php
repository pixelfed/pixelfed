<!DOCTYPE html>
@auth
<html lang="{{ app()->getLocale() }}">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<meta name="mobile-web-app-capable" content="yes">

	<title>{{ $title ?? config_cache('app.name') }}</title>
	<link rel="manifest" href="/manifest.json">

	<meta property="og:site_name" content="{{ config_cache('app.name') }}">
	<meta property="og:title" content="{{ $title ?? config_cache('app.name') }}">
	<meta property="og:type" content="article">
	<meta property="og:url" content="{{url(request()->url())}}">
	@stack('meta')

	<meta name="medium" content="image">
	<meta name="theme-color" content="#10c5f8">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<link rel="shortcut icon" type="image/png" href="/img/favicon.png?v=2">
	<link rel="apple-touch-icon" type="image/png" href="/img/favicon.png?v=2">
	<link rel="canonical" href="{{url(request()->url())}}">
	@if(request()->cookie('dark-mode'))

	<link href="{{ mix('css/appdark.css') }}" rel="stylesheet" data-stylesheet="dark">
	@else

	<link href="{{ mix('css/app.css') }}" rel="stylesheet" data-stylesheet="light">
	@endif

	@stack('styles')

	@if(config_cache('uikit.show_custom.css'))
	<style type="text/css">{!!config_cache('uikit.custom.css')!!}</style>
	@endif

	<script type="text/javascript">window._sharedData = {curUser: {}, version: 0}; window.App = {config: {!!App\Util\Site\Config::json()!!}};</script>

</head>
<body class="loggedIn">
	@include('layouts.partial.nav')
	<main id="content">
		@yield('content')
		<noscript>
			<div class="container">
				<p class="pt-5 text-center lead">Please enable javascript to view this content.</p>
			</div>

		</noscript>
	</main>
	@include('layouts.partial.footer')
	<script type="text/javascript" src="{{ mix('js/manifest.js') }}"></script>
	<script type="text/javascript" src="{{ mix('js/vendor.js') }}"></script>
	<script type="text/javascript" src="{{ mix('js/app.js') }}"></script>
	<script type="text/javascript" src="{{ mix('js/components.js') }}"></script>
	@stack('scripts')
	<div class="mobile-footer-spacer d-block d-sm-none mt-5"></div>
	<div class="mobile-footer d-block d-sm-none fixed-bottom">
		<div class="card card-body rounded-0 pt-2 pb-4 box-shadow" style="border-top:1px solid #F1F5F8">
			<ul class="nav nav-pills nav-fill d-flex align-items-middle">
			  <li class="nav-item">
				<a class="nav-link text-dark" href="/"><i class="fal fa-home fa-2x"></i></a>
			  </li>
			  <li class="nav-item">
				<a class="nav-link text-dark" href="/discover"><i class="fal fa-search fa-2x"></i></a>
			  </li>
			  <li class="nav-item">
				<div class="nav-link cursor-pointer text-dark" onclick="App.util.compose.post()">
					<i class="fal fa-plus-circle fa-2x"></i>
				</div>
			  </li>
			  <li class="nav-item">
				<a class="nav-link text-dark" href="/account/activity"><i class="fal fa-bell fa-2x"></i></a>
			  </li>
			  <li class="nav-item">
				<a class="nav-link text-dark" href="/i/me"><i class="fal fa-user fa-2x"></i></a>
			  </li>
			</ul>
		</div>
	</div>
</body>
</html>
@endauth

@guest
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="mobile-web-app-capable" content="yes">

	<title>{{ $title ?? config('app.name', 'Pixelfed') }}</title>
	<link rel="manifest" href="/manifest.json">

	<meta property="og:site_name" content="{{ config('app.name', 'pixelfed') }}">
	<meta property="og:title" content="{{ $title ?? config('app.name', 'pixelfed') }}">
	<meta property="og:type" content="article">
	<meta property="og:url" content="{{url(request()->url())}}">
	@stack('meta')

	<meta name="medium" content="image">
	<meta name="theme-color" content="#10c5f8">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<link rel="shortcut icon" type="image/png" href="/img/favicon.png?v=2">
	<link rel="apple-touch-icon" type="image/png" href="/img/favicon.png?v=2">
	<link rel="canonical" href="{{url(request()->url())}}">
	<link href="{{ mix('css/app.css') }}" rel="stylesheet" data-stylesheet="light">
	<script type="text/javascript">window._sharedData = {curUser: {}, version: 0}; window.App = {config: {!!App\Util\Site\Config::json()!!}};</script>
	@stack('styles')
</head>
<body>
	@include('layouts.partial.nav')
	<main id="content">
		@yield('content')
	</main>
	@include('layouts.partial.footer')
	<script type="text/javascript" src="{{ mix('js/manifest.js') }}"></script>
	<script type="text/javascript" src="{{ mix('js/vendor.js') }}"></script>
	<script type="text/javascript" src="{{ mix('js/app.js') }}"></script>
	<script type="text/javascript" src="{{ mix('js/components.js') }}"></script>
	@stack('scripts')
</body>
</html>
@endguest
