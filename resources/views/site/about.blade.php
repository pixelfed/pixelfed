<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>

	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="mobile-web-app-capable" content="yes">
	<title>{{ config('app.name', 'Pixelfed') }}</title>
	<meta property="og:site_name" content="{{ config_cache('app.name', 'pixelfed') }}">
	<meta property="og:title" content="{{ config_cache('app.name', 'pixelfed') }}">
	<meta property="og:type" content="article">
	<meta property="og:url" content="{{route('site.about')}}">
	<meta property="og:description" content="{{config_cache('app.short_description')}}">
	<meta name="medium" content="image">
	<meta name="theme-color" content="#10c5f8">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<link rel="shortcut icon" type="image/png" href="/img/favicon.png?v=2">
	<link rel="apple-touch-icon" type="image/png" href="/img/favicon.png?v=2">
	<link href="{{ mix('css/app.css') }}" rel="stylesheet">
	<style type="text/css">
		.section-spacer {
			height: 13vh;
		}
	</style>
</head>
<body>
	<main id="content">
		<div class="container">
			<p class="text-right mt-3">
				<a href="/" class="font-weight-bold text-dark">Home</a>
				<a href="/site/newsroom" class="ml-4 font-weight-bold text-dark">Newsroom</a>
			</p>
		</div>
		<div class="px-4 py-5 my-5 text-center">
			<a href="/">
				<img class="d-block mx-auto mb-4" src="/img/pixelfed-icon-color.svg" alt="" width="72" height="57">
			</a>
			<h1 class="display-4 font-weight-bold py-3">{{ config_cache('about.title') ?? 'Photo Sharing. For Everyone' }}</h1>
			<div class="col-lg-6 mx-auto py-3">
			  <p class="mb-4 font-weight-light text-left" style="font-size: 26px; line-height: 40px;">
				{!! config_cache('app.description') ?? config_cache('app.short_description') ?? 'Pixelfed is an image sharing platform, an ethical alternative to centralized platforms.'!!}
			  </p>
			</div>
		</div>

		<div class="container">
			<div class="row align-items-stretch pt-5">
				<div class="col">
					<div class="card h-100 shadow-lg" style="background-image: url('/_landing/3.jpeg');min-height:400px;border-radius:1rem;">
					</div>
				</div>

				<div class="col">
					<div class="card h-100 shadow-lg" style="background-image: url('/_landing/8.jpeg');min-height:400px;border-radius:1rem;">
					</div>
				</div>

			</div>

			<div class="row align-items-stretch pt-5">

				<div class="col">
					<div class="card h-100 shadow-lg" style="background-image: url('/_landing/6.jpeg');min-height:200px;border-radius:1rem;background-size: cover;">
					</div>
				</div>

				<div class="col">
					<div class="card h-100 shadow-lg" style="background-image: url('/_landing/4.jpeg');min-height:200px;border-radius:1rem;background-size: cover;">
					</div>
				</div>

				<div class="col">
					<div class="card h-100 shadow-lg" style="background-image: url('/_landing/7.jpeg');min-height:200px;border-radius:1rem;background-size: cover;">
					</div>
				</div>

			</div>
			<div class="row align-items-stretch py-5">

				<div class="col">
					<div class="card h-100 shadow-lg" style="background-image: url('/_landing/1.jpeg');min-height:200px;border-radius:1rem;background-size: cover;">
					</div>
				</div>

				<div class="col">
					<div class="card h-100 shadow-lg" style="background-image: url('/_landing/5.jpeg');min-height:200px;border-radius:1rem;background-size: cover;">
					</div>
				</div>

				<div class="col">
					<div class="card h-100 shadow-lg" style="background-image: url('/_landing/9.jpeg');min-height:200px;border-radius:1rem;background-size: cover;">
					</div>
				</div>

			</div>
		</div>

		@if($rules)
		<div class="section-spacer"></div>
		<div class="section-spacer"></div>

		<div id="rules" class="container">
			<div class="row mb-4">
				<div class="col">
					<h1 class="display-4 font-weight-bold mb-0 text-center">Rules</h1>
				</div>
			</div>
			<div class="row justify-content-center">
				<div class="col-12 mb-2 col-lg-8 mb-lg-0">
					<ol>
						@foreach($rules as $rule)
						<li class="h3 my-4">{{$rule}}</li>
						@endforeach
					</ol>
					<p class="h5 text-center pt-4">For more information, please review our <a href="/site/terms">Terms of Use</a></p>
				</div>
			</div>
		</div>

		<div class="section-spacer"></div>
		<div class="section-spacer"></div>
		@endif

		<section class="container">
			<div class="row">
				<div class="col-12 col-md-8 offset-md-2">
					<div class="mt-5">
						<p class="text-center display-4 font-weight-bold">Feature Packed.</p>
					</div>
					<div class="my-2">
						<p class="h3 font-weight-light text-muted text-center">The best for the brightest 📸</p>
					</div>
				</div>
			</div>
		</section>
		<div class="container my-5">
			<div class="row p-4 pb-0 pt-lg-5 align-items-center rounded-3">
				<div class="col-lg-6 p-3 p-lg-5 pt-lg-3">
					<h1 class="display-4 font-weight-bold lh-1">Albums</h1>
					<p class="h4 font-weight-light">Share posts with up to {{config_cache('pixelfed.max_album_length')}} photos</p>
				</div>
				<div class="col-lg-6 overflow-hidden">
					<img class="rounded-lg img-fluid filter-inkwell" src="/_landing/1.jpeg" alt="" width="720">
				</div>
			</div>
		</div>
		<div class="section-spacer"></div>
		<div class="container my-5">
			<div class="row p-4 pb-0 pt-lg-5 align-items-center rounded-3">
				<div class="col-lg-6 overflow-hidden">
					<img class="rounded-lg img-fluid filter-inkwell" src="/_landing/2.jpeg" alt="" width="720">
				</div>
				<div class="col-lg-6 p-3 p-lg-5 pt-lg-3">
					<h1 class="display-4 font-weight-bold lh-1">Comments</h1>
					<p class="h4 font-weight-light text-justify">Comment on a post, or send a reply</p>
				</div>
			</div>
		</div>
		<div class="section-spacer"></div>
		<div class="container my-5">
			<div class="row p-4 pb-0 pt-lg-5 align-items-center rounded-3">
				<div class="col-lg-6 p-3 p-lg-5 pt-lg-3">
					<h1 class="display-4 font-weight-bold lh-1">Collections</h1>
					<p class="h4 font-weight-light text-justify">Organize and share collections of multiple posts</p>
				</div>
				<div class="col-lg-6 overflow-hidden">
					<img class="rounded-lg img-fluid filter-inkwell" src="/_landing/3.jpeg" alt="" width="720">
				</div>
			</div>
		</div>
		<div class="section-spacer"></div>
		<div class="container my-5">
			<div class="row p-4 pb-0 pt-lg-5 align-items-center rounded-3">
				<div class="col-lg-6 overflow-hidden">
					<img class="rounded-lg img-fluid filter-inkwell" src="/_landing/4.jpeg" alt="" width="720">
				</div>
				<div class="col-lg-6 p-3 p-lg-5 pt-lg-3">
					<h1 class="display-4 font-weight-bold lh-1">Discover</h1>
					<p class="h4 font-weight-light text-justify">Explore categories, hashtags and topics</p>
				</div>
			</div>
		</div>
		<div class="section-spacer"></div>
		<div class="container my-5">
			<div class="row p-4 pb-0 pt-lg-5 align-items-center rounded-3">
				<div class="col-lg-6 p-3 p-lg-5 pt-lg-3">
					<h1 class="display-4 font-weight-bold lh-1">Photo Filters</h1>
					<p class="h4 font-weight-light text-justify">Add a special touch to your photos</p>
				</div>
				<div class="col-lg-6 overflow-hidden">
					<img class="rounded-lg img-fluid filter-inkwell" src="/_landing/5.jpeg" alt="" width="720">
				</div>
			</div>
		</div>
		<div class="section-spacer"></div>
		<div class="container my-5">
			<div class="row p-4 pb-0 pt-lg-5 align-items-center rounded-3">
				<div class="col-lg-6 overflow-hidden">
					<img class="rounded-lg img-fluid filter-inkwell" src="/_landing/6.jpeg" alt="" width="720">
				</div>
				<div class="col-lg-6 p-3 p-lg-5 pt-lg-3">
					<h1 class="display-4 font-weight-bold lh-1">Stories</h1>
					<p class="h4 font-weight-light text-justify">Share moments with your followers that disappear after 24 hours</p>
				</div>
			</div>
		</div>

		<div class="section-spacer"></div>
		<div class="section-spacer"></div>

		<div id="stats" class="container">
			<div class="row mb-4">
				<div class="col">
					<p class="display-3 font-weight-bold">
						<span class="text-primary">{{$user_count}}</span>
						people have shared
						<span class="text-primary">{{$post_count}}</span>
						photos and videos on {{config_cache('app.name')}}!
					</p>
					@if(config_cache('pixelfed.open_registration'))
					<div class="section-spacer"></div>
					<p class="display-4 font-weight-bold mb-0">
						<a class="text-primary" href="/register">Sign up today</a>
						and join our community of photographers from around the world.
					</p>
					@endif
				</div>
			</div>
		</div>

		<div class="section-spacer"></div>
		<div class="section-spacer"></div>
	</main>
  </div>
	@include('layouts.partial.footer')
</body>
</html>
