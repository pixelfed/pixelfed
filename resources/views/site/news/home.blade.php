@extends('site.news.partial.layout')

@section('body')
<div class="container">
	<div class="row px-3">
		@foreach($posts->slice(0,1) as $post)
		<div class="col-12 bg-light d-flex justify-content-center align-items-center mt-2 mb-4" style="height:300px;">
			<div class="mx-5">
				<p class="small text-danger mb-0 text-uppercase">{{$post->category}}</p>
				<p class="small text-muted">{{$post->published_at->format('F d, Y')}}</p>
				<p class="h1" style="font-size: 2.6rem;font-weight: 700;"><a class="text-dark text-decoration-none" href="{{$post->permalink()}}">{{$post->title}}</a></p>
			</div>
		</div>
		@endforeach
		@foreach($posts->slice(1) as $post)
		<div class="col-6 bg-light d-flex justify-content-center align-items-center mt-3 px-5" style="height:300px;">
			<div class="mx-0">
				<p class="small text-danger mb-0 text-uppercase">{{$post->category}}</p>
				<p class="small text-muted">{{$post->published_at->format('F d, Y')}}</p>
				<p class="h1" style="font-size: 2rem;font-weight: 700;"><a class="text-dark text-decoration-none" href="{{$post->permalink()}}">{{$post->title}}</a></p>
			</div>
		</div>
		@endforeach
	</div>
</div>
@endsection