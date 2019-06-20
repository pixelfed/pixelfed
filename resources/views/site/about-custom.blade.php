@extends('layouts.anon')

@section('content')
<div class="jumbotron jumbotron-fluid bg-primary text-white mb-0 py-4">
	<div class="container">
		<p class="h1 font-weight-light">About</p>
		<p class="h3 font-weight-light py-4">{{$page->title ?? 'Pixelfed is an image sharing platform, an ethical alternative to centralized platforms.'}}</p>
	</div>
</div>
<div class="bg-white">
	<div class="container d-flex justify-content-center">
		<div class="card mr-3" style="width:800px;margin-top:-30px;">
			<div class="card-body">
				{!! $page->content !!}
			</div>
		</div>
		<div style="width:300px;margin-top:-30px;text-align: center;">
			<div class="card border-left-blue mb-3">
				<div class="card-body">
					<p class="h2 mb-0">{{$stats['posts']}}</p>
					<p class="font-weight-bold mb-0">Posts</p>
				</div>
			</div>

			<div class="card border-left-blue mb-3">
				<div class="card-body">
					<p class="h2 mb-0">{{$stats['users']}}</p>
					<p class="font-weight-bold mb-0">Users</p>
				</div>
			</div>

			@if($stats['admin'])
			<div class="card border-left-blue mb-3">
				<div class="card-body">
					<p class="h2 mb-0">
						<a href="{{$stats['admin']->url()}}">
							&commat;{{$stats['admin']->username}}
						</a>
					</p>
					<p class="font-weight-bold mb-0">Instance Admin</p>
				</div>
			</div>
			@endif
		</div>
	</div>
	<div class="container py-5">
	</div>
</div>
<div class="bg-primary pt-5 pb-4">
	<div class="container">
		<div class="row">
			<div class="col-12 col-md-4 mb-4">
				<div class="card bg-transparent" style="box-shadow: none;border:1px solid #fff">
					<div class="card-body text-white text-center">
						<p class="font-weight-bold lead mb-0">
							Ad Free
						</p>
						<p class="font-weight-light mb-0">No Ads or Trackers</p>
					</div>
				</div>
			</div>
			<div class="col-12 col-md-4 mb-4">
				<div class="card bg-transparent" style="box-shadow: none;border:1px solid #fff">
					<div class="card-body text-white text-center">
						<p class="font-weight-bold lead mb-0">
							Chronological
						</p>
						<p class="font-weight-light mb-0">Timelines in order</p>
					</div>
				</div>
			</div>
			<div class="col-12 col-md-4 mb-4">
				<div class="card bg-transparent" style="box-shadow: none;border:1px solid #fff">
					<div class="card-body text-white text-center">
						<p class="font-weight-bold lead mb-0">
							Federated
						</p>
						<p class="font-weight-light mb-0">A network of millions</p>
					</div>
				</div>
			</div>
			<div class="col-12 col-md-4 mb-4">
				<div class="card bg-transparent" style="box-shadow: none;border:1px solid #fff">
					<div class="card-body text-white text-center">
						<p class="font-weight-bold lead mb-0">
							Discover
						</p>
						<p class="font-weight-light mb-0">Discover popular posts</p>
					</div>
				</div>
			</div>
			<div class="col-12 col-md-4 mb-4">
				<div class="card bg-transparent" style="box-shadow: none;border:1px solid #fff">
					<div class="card-body text-white text-center">
						<p class="font-weight-bold lead mb-0">
							Photo Filters
						</p>
						<p class="font-weight-light mb-0">Add an optional filter</p>
					</div>
				</div>
			</div>
			<div class="col-12 col-md-4 mb-4">
				<div class="card bg-transparent" style="box-shadow: none;border:1px solid #fff">
					<div class="card-body text-white text-center">
						<p class="font-weight-bold lead mb-0">
							Stories
						</p>
						<p class="font-weight-light mb-0">Coming Soon!</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection

@push('meta')
<meta property="og:description" content="Pixelfed is an image sharing platform, an ethical alternative to centralized platforms.">
@endpush