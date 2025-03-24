<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>

	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="mobile-web-app-capable" content="yes">
	<title>{{ config_cache('app.name') ?? 'pixelfed' }}</title>
	<meta property="og:site_name" content="{{ config_cache('app.name') ?? 'pixelfed' }}">
	<meta property="og:title" content="{{ config_cache('app.name') ?? 'pixelfed' }}">
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
			
			<h1 class="display-4 font-weight-bold py-3">{{ config_cache('about.title') ?? __('site.photo_sharing_for_everyone') }}</h1>
			<div class="col-lg-6 mx-auto py-3">
			  <p class="mb-4 font-weight-light text-left" style="font-size: 26px; line-height: 40px;">
				{!! config_cache('app.description') ?? config_cache('app.short_description') ?? __('site.pixelfed_is_an_image_sharing_platform_etc') !!}
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
						<p class="text-center display-4 font-weight-bold">{{__('site.feature_packed')}}</p>
					</div>
					<div class="my-2">
						<p class="h3 font-weight-light text-muted text-center">{{__('site.the_best_for_the_brightest')}}</p>
					</div>
				</div>
			</div>
		</section>
		<div class="container my-5">
			<div class="row p-4 pb-0 pt-lg-5 align-items-center rounded-3">
				<div class="col-lg-6 p-3 p-lg-5 pt-lg-3">
					<h1 class="display-4 font-weight-bold lh-1">{{__('site.albums')}}</h1>
					<p class="h4 font-weight-light">{{__('site.share_posts_with_up_to')}} {{config_cache('pixelfed.max_album_length')}} {{__('site.photos')}}</p>
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
					<h1 class="display-4 font-weight-bold lh-1">{{__('site.comments')}}</h1>
					<p class="h4 font-weight-light text-justify">{{__('site.comment_on_a_post_or_send_a_reply')}}</p>
				</div>
			</div>
		</div>
		<div class="section-spacer"></div>
		<div class="container my-5">
			<div class="row p-4 pb-0 pt-lg-5 align-items-center rounded-3">
				<div class="col-lg-6 p-3 p-lg-5 pt-lg-3">
					<h1 class="display-4 font-weight-bold lh-1">{{__('site.collections')}}</h1>
					<p class="h4 font-weight-light text-justify">{{__('site.organize_and_share_collections_of_multiple_posts')}}</p>
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
					<h1 class="display-4 font-weight-bold lh-1">{{__('site.discover')}}</h1>
					<p class="h4 font-weight-light text-justify">{{__('site.explore_categories_hashtags_and_topics')}}</p>
				</div>
			</div>
		</div>
		<div class="section-spacer"></div>
		<div class="container my-5">
			<div class="row p-4 pb-0 pt-lg-5 align-items-center rounded-3">
				<div class="col-lg-6 p-3 p-lg-5 pt-lg-3">
					<h1 class="display-4 font-weight-bold lh-1">{{__('site.photo_filters')}}</h1>
					<p class="h4 font-weight-light text-justify">{{__('site.add_a_special_touch_to_your_photos')}}</p>
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
					<h1 class="display-4 font-weight-bold lh-1">{{__('site.stories')}}</h1>
					<p class="h4 font-weight-light text-justify">{{__('site.share_moments_with_your_followers_that_disappear_etc')}}</p>
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
						{{__('site.people_have_shared')}}
						<span class="text-primary">{{$post_count}}</span>
						{{__('site.photos_and_videos_on')}} {{config_cache('app.name')}}!
					</p>
					@if(config_cache('pixelfed.open_registration'))
					<div class="section-spacer"></div>
					<p class="display-4 font-weight-bold mb-0">
						<a class="text-primary" href="/register">{{__('site.sign_up_today')}}</a>
						{{__('site.and_join_our_community_of_photographers_from_etc')}}
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
