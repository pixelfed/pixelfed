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
	<div class="alert alert-warning px-3 h6">
		We are deprecating Labs in a future version. Some features will no longer be supported. For more information, click <a href="{{route('help.labs-deprecation')}}" class="font-weight-bold">here</a>.
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
		@if($profile->profile_layout == 'moment')
		<div class="form-check pb-3">
			<label class="form-check-label font-weight-bold mb-3" for="profile_layout">
				{{__('MomentUI Profile Header Color')}}
			</label>
			<div class="row">
				<div class="col-6 col-sm-3 pb-5">
					<div class="">
						<p class="form-check-label">
							<div class="bg-pixelfed rounded-circle box-shadow" style="width:60px; height:60px"></div>
						</p>
						<p class="mb-0 small text-muted">Default</p>
						<input class="form-check-input mx-0 pl-0" type="radio" name="moment_bg" value="default" {{$profile->header_bg == 'default' || !$profile->header_bg ? 'checked':''}}>
					</div>
				</div>
				<div class="col-6 col-sm-3 pb-5">
					<div class="">
						<p class="form-check-label">
							<div class="bg-moment-azure rounded-circle box-shadow" style="width:60px; height:60px"></div>
						</p>
						<p class="mb-0 small text-muted">Azure</p>
						<input class="form-check-input mx-0" type="radio" name="moment_bg" value="azure" {{$profile->header_bg == 'azure' ? 'checked':''}}>
					</div>
				</div>
				<div class="col-6 col-sm-3 pb-5">
					<div class="">
						<p class="form-check-label">
							<div class="bg-moment-passion rounded-circle box-shadow" style="width:60px; height:60px"></div>
						</p>
						<p class="mb-0 small text-muted">Passion</p>
						<input class="form-check-input mx-0" type="radio" name="moment_bg" value="passion" {{$profile->header_bg == 'passion' ? 'checked':''}}>
					</div>
				</div>
				<div class="col-6 col-sm-3 pb-5">
					<div class="">
						<p class="form-check-label">
							<div class="bg-moment-reef rounded-circle box-shadow" style="width:60px; height:60px"></div>
						</p>
						<p class="mb-0 small text-muted">Reef</p>
						<input class="form-check-input mx-0" type="radio" name="moment_bg" value="reef" {{$profile->header_bg == 'reef' ? 'checked':''}}>
					</div>
				</div>
				<div class="col-6 col-sm-3 pb-5">
					<div class="">
						<p class="form-check-label">
							<div class="bg-moment-lush rounded-circle box-shadow" style="width:60px; height:60px"></div>
						</p>
						<p class="mb-0 small text-muted">Lush</p>
						<input class="form-check-input mx-0" type="radio" name="moment_bg" value="lush" {{$profile->header_bg == 'lush' ? 'checked':''}}>
					</div>
				</div>
				<div class="col-6 col-sm-3 pb-5">
					<div class="">
						<p class="form-check-label">
							<div class="bg-moment-neon rounded-circle box-shadow" style="width:60px; height:60px"></div>
						</p>
						<p class="mb-0 small text-muted">Neon</p>
						<input class="form-check-input mx-0" type="radio" name="moment_bg" value="neon" {{$profile->header_bg == 'neon' ? 'checked':''}}>
					</div>
				</div>
				<div class="col-6 col-sm-3 pb-5">
					<div class="">
						<p class="form-check-label">
							<div class="bg-moment-flare rounded-circle box-shadow" style="width:60px; height:60px"></div>
						</p>
						<p class="mb-0 small text-muted">Flare</p>
						<input class="form-check-input mx-0" type="radio" name="moment_bg" value="flare" {{$profile->header_bg == 'flare' ? 'checked':''}}>
					</div>
				</div>
				<div class="col-6 col-sm-3 pb-5">
					<div class="">
						<p class="form-check-label">
							<div class="bg-moment-morning rounded-circle box-shadow" style="width:60px; height:60px"></div>
						</p>
						<p class="mb-0 small text-muted">Morning</p>
						<input class="form-check-input mx-0" type="radio" name="moment_bg" value="morning" {{$profile->header_bg == 'morning' ? 'checked':''}}>
					</div>
				</div>
				<div class="col-6 col-sm-3 pb-5">
					<div class="">
						<p class="form-check-label">
							<div class="bg-moment-tranquil rounded-circle box-shadow" style="width:60px; height:60px"></div>
						</p>
						<p class="mb-0 small text-muted">Tranquil</p>
						<input class="form-check-input mx-0" type="radio" name="moment_bg" value="tranquil" {{$profile->header_bg == 'tranquil' ? 'checked':''}}>
					</div>
				</div>
				<div class="col-6 col-sm-3 pb-5">
					<div class="">
						<p class="form-check-label">
							<div class="bg-moment-mauve rounded-circle box-shadow" style="width:60px; height:60px"></div>
						</p>
						<p class="mb-0 small text-muted">Mauve</p>
						<input class="form-check-input mx-0" type="radio" name="moment_bg" value="mauve" {{$profile->header_bg == 'mauve' ? 'checked':''}}>
					</div>
				</div>
				<div class="col-6 col-sm-3 pb-5">
					<div class="">
						<p class="form-check-label">
							<div class="bg-moment-argon rounded-circle box-shadow" style="width:60px; height:60px"></div>
						</p>
						<p class="mb-0 small text-muted">Argon</p>
						<input class="form-check-input mx-0" type="radio" name="moment_bg" value="argon" {{$profile->header_bg == 'argon' ? 'checked':''}}>
					</div>
				</div>
				<div class="col-6 col-sm-3 pb-5">
					<div class="">
						<p class="form-check-label">
							<div class="bg-moment-royal rounded-circle box-shadow" style="width:60px; height:60px"></div>
						</p>
						<p class="mb-0 small text-muted">Royal</p>
						<input class="form-check-input mx-0" type="radio" name="moment_bg" value="royal" {{$profile->header_bg == 'royal' ? 'checked':''}}>
					</div>
				</div>
			</div>
			<p class="text-muted small help-text">Set your MomentUI profile background color. Adding a custom header image will be supported in a future version.</p>
		</div>
		@endif
		<div class="form-check pb-3">
			<input class="form-check-input" type="checkbox" name="dark_mode" id="dark_mode" {{request()->hasCookie('dark-mode') ? 'checked':''}}>
			<label class="form-check-label font-weight-bold" for="dark_mode">
				{{__('MetroUI Dark Mode')}}
			</label>
			<p class="text-muted small help-text">Use dark mode theme.</p>
		</div>
		@if(config('exp.rec') == true)
		<div class="form-check pb-3">
			<input class="form-check-input" type="checkbox" name="show_suggestions" id="show_suggestions">
			<label class="form-check-label font-weight-bold" for="show_suggestions">
				{{__('Profile Suggestions')}}
			</label>
			<p class="text-muted small help-text">Show Profile Suggestions.</p>
		</div>
		@endif
		<div class="form-check pb-3">
			<input class="form-check-input" type="checkbox" name="show_readmore" id="show_readmore">
			<label class="form-check-label font-weight-bold" for="show_readmore">
				{{__('Use Read More')}}
			</label>
			<p class="text-muted small help-text">Collapses captions/comments more than 3 lines.</p>
		</div>
		<div class="form-check pb-3">
			<input class="form-check-input" type="checkbox" id="distraction_free">
			<label class="form-check-label font-weight-bold">Simple Mode (Timelines only)</label>
			<p class="text-muted small help-text">An experimental content-first timeline layout</p>
		</div>
		<div class="form-check pb-3">
			<input class="form-check-input" type="checkbox" id="show_tips">
			<label class="form-check-label font-weight-bold">Show Announcements</label>
			<p class="text-muted small help-text">Show Announcements on Timelines (Desktop Only)</p>
		</div>
		<div class="form-check pb-3">
			<input class="form-check-input" type="checkbox" id="force_metro">
			<label class="form-check-label font-weight-bold">Force Metro Layout</label>
			<p class="text-muted small help-text">Force MetroUI layout for profiles and posts.</p>
		</div>
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

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {
	let showSuggestions = localStorage.getItem('pf_metro_ui.exp.rec') == 'false' ? false : true;
	let showReadMore = localStorage.getItem('pf_metro_ui.exp.rm') == 'false' ? false : true;
	let distractionFree = localStorage.getItem('pf_metro_ui.exp.df') == 'true' ? true : false;
	let forceMetro = localStorage.getItem('pf_metro_ui.exp.forceMetro') == 'true' ? true : false;

	if(showSuggestions == true) {
		$('#show_suggestions').attr('checked', true);
	}

	if(showReadMore == true) {
		$('#show_readmore').attr('checked', true);
	}

	if(distractionFree == true) {
		$('#distraction_free').attr('checked', true);
	}

	if(localStorage.getItem('metro-tips') !== 'false') {
		$('#show_tips').attr('checked', true);
	}

	if(forceMetro == true) {
		$('#force_metro').attr('checked', true);
	}

	$('#show_suggestions').on('change', function(e) {
		if(e.target.checked) {
			localStorage.removeItem('pf_metro_ui.exp.rec');
		} else {
			localStorage.setItem('pf_metro_ui.exp.rec', false);
		}
	});

	$('#show_readmore').on('change', function(e) {
		if(e.target.checked) {
			localStorage.removeItem('pf_metro_ui.exp.rm');
		} else {
			localStorage.setItem('pf_metro_ui.exp.rm', false);
		}
	});

	$('#distraction_free').on('change', function(e) {
		if(e.target.checked) {
			localStorage.setItem('pf_metro_ui.exp.df', true);
		} else {
			localStorage.removeItem('pf_metro_ui.exp.df');
		}
	});

	$('#show_tips').on('change', function(e) {
		if(e.target.checked) {
			localStorage.setItem('metro-tips', true);
		} else {
			localStorage.removeItem('metro-tips');
		}
	});

	$('#force_metro').on('change', function(e) {
		if(e.target.checked) {
			localStorage.setItem('pf_metro_ui.exp.forceMetro', true);
		} else {
			localStorage.removeItem('pf_metro_ui.exp.forceMetro');
		}
	})
});
</script>
@endpush