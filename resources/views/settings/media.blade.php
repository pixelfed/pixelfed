@extends('settings.template')

@section('section')

	<div class="title">
		<h3 class="font-weight-bold">Media</h3>
	</div>
	<hr>
	<form method="post">
		@csrf
		<div class="form-group pb-3">
			<label class="form-check-label font-weight-bold" for="">Default License</label>
			<select class="form-control" name="default">
				@foreach(App\Util\Media\License::get() as $license)
				<option value="{{$license['id']}}" {{$compose['default_license'] == $license['id'] ? 'selected':''}}>
					{{$license['name']}}
					@if($license['id'] > 10)
					({{$license['title']}})
					@endif
				</option>
				@endforeach
			</select>
			<p class="text-muted small help-text">Set a default license for new posts.</p>
		</div>

		<div class="form-check pb-3">
			<input class="form-check-input" type="checkbox" name="sync">
			<label class="form-check-label font-weight-bold" for="">Sync Licenses</label>
			<p class="text-muted small help-text">Update existing posts with your new default license. You can sync once every 24 hours.</p>
		</div>

		<div class="form-check pb-3">
			<input class="form-check-input" type="checkbox" name="media_descriptions" {{$compose['media_descriptions'] == $license['id'] ? 'checked':''}}>
			<label class="form-check-label font-weight-bold" for="">Require Media Descriptions</label>
			<p class="text-muted small help-text">
				Briefly describe your media to improve accessibility for vision impaired people. <br />
				<span class="font-weight-bold">Not available for mobile or 3rd party apps at this time.</span>
			</p>
		</div>

		<div class="form-group row mt-5 pt-5">
			<div class="col-12 text-right">
				<hr>
				<button type="submit" class="btn btn-primary font-weight-bold py-0 px-5">Submit</button>
			</div>
		</div>
	</form>

@endsection
