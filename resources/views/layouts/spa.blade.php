<!DOCTYPE html>
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
	<link href="{{ mix('css/app.css') }}" rel="stylesheet" data-stylesheet="light">
	<link href="{{ mix('css/spa.css') }}" rel="stylesheet" data-stylesheet="light">
	@auth
	<script type="text/javascript">
		window._sharedData = {
			curUser: {},
			user: {!! json_encode(\App\Services\ProfileService::get(request()->user()->profile_id)) !!},
			version: 0
		};
		window.App = {
			config: {!!App\Util\Site\Config::json()!!}
		};
	</script>
	@endauth
</head>
	<body class="loggedIn">
		<main id="content">
			<noscript>
				<div class="container">
					<p class="pt-5 text-center lead">Please enable javascript to view this content.</p>
				</div>
			</noscript>
			<navbar></navbar>
			<router-view></router-view>
		</main>
		<script type="text/javascript" src="{{ mix('js/manifest.js') }}"></script>
		<script type="text/javascript" src="{{ mix('js/vendor.js') }}"></script>
		<script type="text/javascript" src="{{ mix('js/spa.js') }}"></script>
	</body>
</html>
