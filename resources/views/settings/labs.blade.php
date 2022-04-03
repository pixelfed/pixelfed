	@extends('settings.template')

	@section('section')
	<div class="title">
		<h3 class="font-weight-bold">Labs</h3>
		<p class="lead">Experimental features</p>
	</div>
	<hr>
	<form method="post" id="form">
		@csrf
		<div class="form-check pb-3">
			<input class="form-check-input" type="checkbox" name="dark_mode" id="dark_mode" {{request()->hasCookie('dark-mode') ? 'checked':''}}>
			<label class="form-check-label font-weight-bold" for="dark_mode">
				{{__('Dark Mode')}}
			</label>
			<p class="text-muted small help-text">Use dark mode theme.</p>
		</div>

		<div class="form-group row">
			<div class="col-12">
				<hr>
				<button type="button" class="btn btn-primary font-weight-bold py-1 btn-block" id="save-btn">Save Changes</button>
			</div>
		</div>
	</form>
	@endsection

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {
	let darkMode = localStorage.getItem('pf_m2s.color-scheme') == 'dark' ? true : false;
	if(darkMode == true) {
		$('#dark_mode').attr('checked', true);
	}

	$('#save-btn').click(function() {
		let darkMode = document.querySelector('#dark_mode').checked;
		let colorScheme = darkMode ? 'dark' : 'light';
		localStorage.setItem('pf_m2s.color-scheme', colorScheme);
		$('#form').submit();
	});
});
</script>
@endpush
