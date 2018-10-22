@extends('site.help.partial.template', ['breadcrumb'=>'Sharing Photos & Videos'])

@section('section')

	<div class="title">
		<h3 class="font-weight-bold">Sharing Photos & Videos</h3>
	</div>
	<hr>
	<p class="font-weight-light">Welcome to Pixelfed.</p>
	<p class="font-weight-light">Pixelfed is a federated media sharing platform inspired by Instagram and 500px.</p>
	<hr>
	<p>
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse1" role="button" aria-expanded="false" aria-controls="collapse1">
			<i class="fas fa-chevron-down mr-2"></i>
			How do I create a post?
		</a>
		<div class="collapse" id="collapse1">
			<div>
				To create an account using a web browser:
				<ol>
					<li>Go to <a href="{{config('app.url')}}">{{config('app.url')}}</a>.</li>
					<li>Click on the <i class="fas fa-camera-retro text-primary"></i> link at the top of the page.</li>
					<li>Upload your photo(s) or video(s), add a caption and set other options.</li>
					<li>Click on the <span class="font-weight-bold">Create Post</span> button.</li>
				</ol>
			</div>
		</div>
	</p>
	<p>	
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse2" role="button" aria-expanded="false" aria-controls="collapse2">
			<i class="fas fa-chevron-down mr-2"></i>
			How do I share a post with multiple photos or videos?
		</a>
		<div class="collapse" id="collapse2">
			<div>
				During the compose process, you can select multiple files at a single time, or add each photo/video individually.
			</div>
		</div>
	</p>
	<p>	
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse3" role="button" aria-expanded="false" aria-controls="collapse3">
			<i class="fas fa-chevron-down mr-2"></i>
			How do I add a caption before sharing my photos or videos on Pixelfed?
		</a>
		<div class="collapse" id="collapse3">
			<div>
				During the compose process, you will see the <span class="font-weight-bold">Caption</span> input. Captions are optional and limited to <span class="font-weight-bold">{{config('pixelfed.max_caption_length')}}</span> characters.
			</div>
		</div>
	</p>
	{{-- <p>	
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse4" role="button" aria-expanded="false" aria-controls="collapse4">
			<i class="fas fa-chevron-down mr-2"></i>
			How do I add a filter to my photos?
		</a>
		<div class="collapse" id="collapse4">
			<div>
				
			</div>
		</div>
	</p> --}}
	{{-- <p>	
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse5" role="button" aria-expanded="false" aria-controls="collapse5">
			<i class="fas fa-chevron-down mr-2"></i>
			How do I add a description to each photo or video for the visually impaired?
		</a>
		<div class="collapse" id="collapse5">
			<div>

			</div>
		</div>
	</p> --}}	
	<p>	
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse6" role="button" aria-expanded="false" aria-controls="collapse6">
			<i class="fas fa-chevron-down mr-2"></i>
			What types of photos or videos can I upload?
		</a>
		<div class="collapse" id="collapse6">
			<div>
				You can upload the following file types:
				<ul>
					@foreach(explode(',', config('pixelfed.media_types')) as $type)
					<li class="font-weight-bold">{{$type}}</li>
					@endforeach
				</ul>
			</div>
		</div>
	</p>
	{{-- <p>	
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse7" role="button" aria-expanded="false" aria-controls="collapse7">
			<i class="fas fa-chevron-down mr-2"></i>
			What is the limit for photo and video file sizes?
		</a>
		<div class="collapse" id="collapse7">
			<div>

			</div>
		</div>
	</p> --}}
	{{-- <p>	
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse8" role="button" aria-expanded="false" aria-controls="collapse8">
			<i class="fas fa-chevron-down mr-2"></i>
			When I share a photo, what's the image resolution?
		</a>
		<div class="collapse" id="collapse8">
			<div>

			</div>
		</div>
	</p> --}}
	{{-- <p>	
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse9" role="button" aria-expanded="false" aria-controls="collapse9">
			<i class="fas fa-chevron-down mr-2"></i>
			Can I edit my post captions, photos or videos after sharing them?
		</a>
		<div class="collapse" id="collapse9">
			<div>

			</div>
		</div>
	</p> --}}
@endsection