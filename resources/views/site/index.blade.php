<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<meta name="mobile-web-app-capable" content="yes">

	<title>{{ config('app.name', 'Pixelfed') }}</title>

	<meta property="og:site_name" content="{{ config('app.name', 'pixelfed') }}">
	<meta property="og:title" content="{{ config('app.name', 'pixelfed') }}">
	<meta property="og:type" content="article">
	<meta property="og:url" content="{{request()->url()}}">
	<meta property="og:description" content="Federated Image Sharing">

	<meta name="medium" content="image">
	<meta name="theme-color" content="#10c5f8">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<link rel="manifest" href="/manifest.json">
	<link rel="icon" type="image/png" href="/img/favicon.png">
	<link rel="apple-touch-icon" type="image/png" href="/img/favicon.png?v=2">
	<link href="{{ mix('css/landing.css') }}" rel="stylesheet">
	<script type="text/javascript">
		window.pfl = {!! App\Services\LandingService::get() !!}
	</script>
</head>
	<body>
		<main id="content">
			<noscript>
				<div class="container">
					<h1 class="pt-5 text-center">Pixelfed</h1>
					<p class="text-center">Decentralized photo sharing social media</p>
					<p class="pt-2 text-center lead">
						<a href="{{ config('app.url') }}/login" class="btn btn-outline-light">Login</a>
					</p>
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
