@extends('site.help.partial.template', ['breadcrumb'=>'Sharing Photos & Videos'])

@section('section')

	<div class="title">
		<h3 class="font-weight-bold">Sharing Photos & Videos</h3>
	</div>
	<hr>
	<p>
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse1" role="button" aria-expanded="false" aria-controls="collapse1">
			<i class="fas fa-chevron-down mr-2"></i>
			How do I create a post?
		</a>
		<div class="collapse" id="collapse1">
			<div>
				To create a post using a desktop web browser:
				<ol>
					<li>Go to <a href="{{config('app.url')}}">{{config('app.url')}}</a>.</li>
					<li>Click on the <i class="fas fa-camera-retro text-primary"></i> link at the top of the page.</li>
					<li>Upload your photo(s) or video(s), add an optional caption and set other options.</li>
					<li>Click on the <span class="font-weight-bold">Create Post</span> button.</li>
				</ol>
			</div>
			<div class="pt-3">
				To create a post using a mobile web browser:
				<ol>
					<li>Go to <a href="{{config('app.url')}}">{{config('app.url')}}</a>.</li>
					<li>Click on the <i class="far fa-plus-square fa-lg"></i> button at the bottom of the page.</li>
					<li>Upload your photo(s) or video(s), add an optional caption and set other options.</li>
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
	<p>	
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse4" role="button" aria-expanded="false" aria-controls="collapse4">
			<i class="fas fa-chevron-down mr-2"></i>
			How do I add a filter to my photos?
		</a>
		<div class="collapse" id="collapse4">
			<div>
				<p class="text-center">
					<span class="alert alert-warning py-2 font-weight-bold">This is an experimental feature, filters are not federated yet!</span>
				</p>
				To add a filter to media during the compose post process:
				<ol>
					<li>
						Click the <span class="btn btn-sm btn-outline-primary py-0">Options <i class="fas fa-chevron-down fa-sm"></i></span> button if media preview is not displayed.
					</li>
					<li>Select a filter from the <span class="font-weight-bold small text-muted">Select Filter</span> dropdown.</li>
				</ol>
			</div>
		</div>
	</p>
	<p>	
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse5" role="button" aria-expanded="false" aria-controls="collapse5">
			<i class="fas fa-chevron-down mr-2"></i>
			How do I add a description to each photo or video for the visually impaired?
		</a>
		<div class="collapse" id="collapse5">
			<div>
				<p class="text-center">
					<span class="alert alert-warning py-2 font-weight-bold">This is an experimental feature!</span>
				</p>
				<p>
					You need to use the experimental compose UI found <a href="/i/compose">here</a>.
				</p>
				<ol>
					<li>Add media by clicking the <span class="btn btn-outline-secondary btn-sm py-0">Add Photo/Video</span> button.</li>
					<li>Set a image description by clicking the <span class="btn btn-outline-secondary btn-sm py-0">Media Description</span> button.</li>
				</ol>
				<p class="small text-muted"><i class="fas fa-info-circle mr-1"></i> Image descriptions are federated to instances where supported.</p>
			</div>
		</div>
	</p>	
	<p>	
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse6" role="button" aria-expanded="false" aria-controls="collapse6">
			<i class="fas fa-chevron-down mr-2"></i>
			What types of photos or videos can I upload?
		</a>
		<div class="collapse" id="collapse6">
			<div>
				You can upload the following media types:
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
	<p>	
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse10" role="button" aria-expanded="false" aria-controls="collapse10">
			<i class="fas fa-chevron-down mr-2"></i>
			How can I disable comments/replies on my post?
		</a>
		<div class="collapse" id="collapse10">
			<div>
				To enable or disable comments/replies using a desktop or mobile browser:
				<ul>
					<li>Open the menu, click the <i class="fas fa-ellipsis-v text-muted mx-2 cursor-pointer"></i> button</li>
					<li>Click on <span class="small font-weight-bold cursor-pointer">Enable Comments</span> or <span class="small font-weight-bold cursor-pointer">Disable Comments</span></li>
				</ul>
			</div>
		</div>
	</p>
	<p>	
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse11" role="button" aria-expanded="false" aria-controls="collapse11">
			<i class="fas fa-chevron-down mr-2"></i>
			How many people can I tag or mention in my comments or posts?
		</a>
		<div class="collapse" id="collapse11">
			<div>
				You can tag or mention up to 5 profiles per comment or post.
			</div>
		</div>
	</p>

@endsection