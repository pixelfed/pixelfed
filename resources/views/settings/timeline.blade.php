@extends('settings.template')

@section('section')

	<div class="title">
		<h3 class="font-weight-bold">Timeline Settings</h3>
	</div>
	<hr>
	<form method="post">
		@csrf
		<div class="form-check pb-3">
			<input class="form-check-input" type="checkbox" name="top" {{$top ? 'checked':''}}>
			<label class="form-check-label font-weight-bold" for="">Show text-only posts</label>
			<p class="text-muted small help-text">Show text-only posts from accounts you follow. (Home timeline only)</p>
		</div>
		<div class="form-check pb-3">
			<input class="form-check-input" type="checkbox" name="replies" {{$replies ? 'checked':''}}>
			<label class="form-check-label font-weight-bold" for="">Show replies</label>
			<p class="text-muted small help-text">Show replies from accounts you follow. (Home timeline only)</p>
		</div>

		<div class="form-group row mt-5 pt-5">
			<div class="col-12 text-right">
				<hr>
				<button type="submit" class="btn btn-primary font-weight-bold py-0 px-5">Submit</button>
			</div>
		</div>
	</form>

@endsection
