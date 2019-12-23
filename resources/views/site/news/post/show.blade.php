@extends('site.news.partial.layout')

@section('body')
<div class="container mt-3">
	<div class="row px-3">
		<div class="col-12 bg-light d-flex justify-content-center align-items-center" style="min-height: 400px">
			<div style="max-width: 550px;">
				<p class="small text-danger mb-0 text-uppercase">{{$post->category}}</p>
				<p class="small text-muted">{{$post->published_at->format('F d, Y')}}</p>
				<p class="h1" style="font-size: 2.6rem;font-weight: 700;">{{$post->title}}</p>
			</div>
		</div>
		<div class="col-12 mt-4">
			<div class="d-flex justify-content-center">
				<p class="lead text-center py-5" style="font-size:25px; font-weight: 200; max-width: 550px;">
					{{$post->summary}}
				</p>
			</div>
		</div>
		@if($post->body)
		<div class="col-12 mt-4">
			<div class="d-flex justify-content-center border-top">
				<p class="lead py-5" style="max-width: 550px;">
					{!!$post->body!!}
				</p>
			</div>
		</div>
		@else
		<div class="col-12 mt-4"></div>
		@endif
	</div>
</div>
@endsection