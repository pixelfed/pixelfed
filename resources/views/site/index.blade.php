<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<meta name="mobile-web-app-capable" content="yes">

	<title>{{ config_cache('app.name', 'Pixelfed') }}</title>

	<link rel="canonical" href="{{ request()->url() }}" />

	<meta property="og:site_name" content="{{ config_cache('app.name', 'pixelfed') }}" />
	<meta property="og:title" content="{{ config_cache('app.name', 'pixelfed') }}" />
	<meta property="og:type" content="website" />
	<meta property="og:url" content="{{request()->url()}}" />
	<meta property="og:image" content="{{ config_cache('app.banner_image') ?? url('storage/headers/default.jpg')}}" />
	<meta property="og:description" content="{{ config_cache('app.short_description') ?? 'Decentralized photo sharing social media powered by Pixelfed' }}" />
	<meta name="description" content="{{ config_cache('app.short_description') ?? 'Decentralized photo sharing social media powered by Pixelfed' }}" />
	<meta name="twitter:title" content="{{ config_cache('app.name', 'pixelfed') }}" />
    <meta name="twitter:description" content="{{ config_cache('app.short_description') ?? 'Decentralized photo sharing social media powered by Pixelfed' }}" />
    <meta name="twitter:image" content="{{ config_cache('app.banner_image') ?? url('storage/headers/default.jpg')}}" />
    <meta name="twitter:card" content="summary_large_image" />

	<meta name="medium" content="image">
	<meta name="theme-color" content="#10c5f8">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<link rel="manifest" href="{{url('/manifest.json')}}" />
	<link rel="icon" type="image/png" href="{{url('/img/favicon.png')}}">
	<link rel="apple-touch-icon" type="image/png" href="{{url('/img/favicon.png')}}">
	<link href="{{ mix('css/landing.css') }}" rel="stylesheet">
	<link rel="preload" as="image" href="{{ url('/_landing/bg.jpg')}}" />
	<script type="text/javascript">
		window.pfl = {!! App\Services\LandingService::get() !!}
	</script>
</head>
	<body>
		<main id="content">
			<noscript>
				<div class="container">
					<h1 class="pt-5 text-center">Pixelfed</h1>
					<p class="pt-2 text-center lead">Please enable javascript to view this content.</p>
				</div>
			</noscript>
			<navbar></navbar>
			<router-view></router-view>
		</main>
		<script type="text/javascript" src="{{ mix('js/manifest.js') }}"></script>
		<script type="text/javascript" src="{{ mix('js/vendor.js') }}"></script>
		<script type="text/javascript" src="{{ mix('js/landing.js') }}"></script>
	</body>
</html>
