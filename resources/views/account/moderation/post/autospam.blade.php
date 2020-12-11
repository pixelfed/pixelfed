@extends('layouts.blank')

@section('content')

<div class="container mt-5">
	<div class="row">
		<div class="col-12 col-md-6 offset-md-3 text-center">
			<p class="h1 pb-2" style="font-weight: 200">Suspicious Activity Detected</p>
			<p class="lead py-3">We detected suspicious activity based on your recent post, it has been flagged for review by our moderation team.</p>
		</div>
		<div class="col-12 col-md-6 offset-md-3">
			<hr>
		</div>
		<div class="col-12 col-md-6 offset-md-3 mt-3">
			<p class="h4 font-weight-bold">Post Details</p>
			@if($interstitial->has_media)
			<div class="py-4 align-items-center">
				<div class="d-block text-center text-truncate">
					@if($interstitial->blurhash)
					<canvas id="mblur" width="400" height="400" class="rounded shadow"></canvas>
					@else
					<img src="/storage/no-preview.png" class="mr-3 img-fluid" alt="No preview available">
					@endif
				</div>
				<div class="mt-2 border rounded p-3">
					@if($meta->caption)
					<p class="text-break">
						Caption: <span class="font-weight-bold">{{$meta->caption}}</span>
					</p>
					@endif
					<p class="mb-0">
						Like Count: <span class="font-weight-bold">{{$meta->likes_count}}</span>
					</p>
					<p class="mb-0">
						Share Count: <span class="font-weight-bold">{{$meta->reblogs_count}}</span>
					</p>
					<p class="">
						Timestamp: <span class="font-weight-bold">{{now()->parse($meta->created_at)->format('r')}}</span>
					</p>
					<p class="mb-0" style="word-break: break-all !important;">
						URL: <span class="font-weight-bold text-primary">{{$meta->url}}</span>
					</p>
				</div>
			</div>
			@else
			<div class="py-4 align-items-center">
				<div class="mt-2 border rounded p-3">
					@if($meta->caption)
					<p class="text-break">
						Comment: <span class="font-weight-bold">{{$meta->caption}}</span>
					</p>
					@endif
					<p class="mb-0">
						Like Count: <span class="font-weight-bold">{{$meta->likes_count}}</span>
					</p>
					<p class="mb-0">
						Share Count: <span class="font-weight-bold">{{$meta->reblogs_count}}</span>
					</p>
					<p class="">
						Timestamp: <span class="font-weight-bold">{{now()->parse($meta->created_at)->format('r')}}</span>
					</p>
					<p class="mb-0" style="word-break: break-all !important;">
						URL: <span class="font-weight-bold text-primary">{{$meta->url}}</span>
					</p>
				</div>
			</div>
			@endif
		</div>
		<div class="col-12 col-md-6 offset-md-3 my-3">
			<div class="border rounded p-3 border-primary">
				<p class="h4 font-weight-bold pt-2 text-primary">Review the Community Guidelines</p>
				<p class="lead pt-4 text-primary">We want to keep {{config('app.name')}} a safe place for everyone, and we created these <a class="font-weight-bold text-primary" href="{{route('help.community-guidelines')}}">Community Guidelines</a> to support and protect our community.</p>
			</div>
		</div>

		<div class="col-12 col-md-6 offset-md-3 mt-4 mb-4">

			<form method="post" action="/i/warning">
				@csrf

				<input type="hidden" name="id" value="{{encrypt($interstitial->id)}}">
				<input type="hidden" name="type" value="{{$interstitial->type}}">
				<input type="hidden" name="action" value="confirm">
				<button type="submit" class="btn btn-primary btn-block font-weight-bold">I Understand</button>
			</form>
		</div>
	</div>
</div>

@endsection

@push('scripts')
@if($interstitial->blurhash)
<script type="text/javascript">
	const pixels = window.blurhash.decode("{{$interstitial->blurhash}}", 400, 400);
	const canvas = document.getElementById("mblur");
	const ctx = canvas.getContext("2d");
	const imageData = ctx.createImageData(400, 400);
	imageData.data.set(pixels);
	ctx.putImageData(imageData, 0, 0);
</script>
@endif
@endpush