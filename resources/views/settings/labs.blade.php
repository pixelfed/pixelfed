	@extends('settings.template')

	@section('section')
	<div class="title">
		<h3 class="font-weight-bold">Labs</h3>
		<p class="lead">Experimental features</p>
	</div>
	<hr>
	<div class="alert alert-primary px-3 h6 text-center">
		<strong>Warning:</strong> Some experimental features may contain bugs or missing functionality
	</div>
	<div class="py-3">
		<p class="font-weight-bold text-muted text-center">UI</p>
		<hr>
	</div>
	<form method="post">
		@csrf
		@if(config('exp.lc') == true)
		<div class="form-check pb-3">
			<input class="form-check-input" type="checkbox" checked disabled>
			<label class="form-check-label font-weight-bold">
				{{__('Hidden like counts on Timelines')}}
			</label>
			<p class="text-muted small help-text">Like counts are hidden on timelines. This experiment was enabled for all users and can only be changed by the instance administrator.</p>
		</div>
		@endif
		<div class="form-check pb-3">
			<input class="form-check-input" type="checkbox" name="profile_layout" id="profile_layout" {{$profile->profile_layout == 'moment' ? 'checked':''}} value="{{$profile->profile_layout}}">
			<label class="form-check-label font-weight-bold" for="profile_layout">
				{{__('Use MomentUI for posts and your profile')}}
			</label>
			<p class="text-muted small help-text">MomentUI offers an alternative layout for posts and your profile.</p>
		</div>
		@if($profile->profile_layout != 'moment')
		<div class="form-check pb-3">
			<input class="form-check-input" type="checkbox" name="dark_mode" id="dark_mode" {{request()->hasCookie('dark-mode') ? 'checked':''}}>
			<label class="form-check-label font-weight-bold" for="dark_mode">
				{{__('MetroUI Dark Mode')}}
			</label>
			<p class="text-muted small help-text">Use dark mode theme.</p>
		</div>
		@endif
		<div class="py-3">
			<p class="font-weight-bold text-muted text-center">Discovery</p>
			<hr>
		</div>
		@if(config('exp.rec') == true)
		<div class="form-check pb-3">
			<input class="form-check-input" type="checkbox" name="profile_suggestions" id="profile_suggestions" {{$profile->is_suggestable ? 'checked' : ''}}>
			<label class="form-check-label font-weight-bold" for="profile_suggestions">
				{{__('Visible on Profile Suggestions')}}
			</label>
			<p class="text-muted small help-text">Allow your profile to be listed in Profile Suggestions.</p>
		</div>
		@endif
		<div class="form-group row">
			<div class="col-12">
				<hr>
				<button type="submit" class="btn btn-primary font-weight-bold py-1 btn-block">Save Changes</button>
			</div>
		</div>
	</form>
	@endsection