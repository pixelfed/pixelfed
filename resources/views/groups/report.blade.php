@extends('layouts.app')

@section('content')
<div class="container">
	<div class="col-12 col-md-6 offset-md-3 mt-5 pt-5">
		<div class="card shadow-none border">
			<div class="card-header font-weight-bold bg-danger text-white">
				Report Group
			</div>
			<div class="card-body">
				{{-- <p class="text-muted">
					Only report groups if they are violating the <a href="#" class="font-weight-bold">Terms of Service</a> or <a href="#" class="font-weight-bold">Community Guidelines</a>.
				</p> --}}
				<p class="font-weight-bold text-muted">Reason (select one)</p>
				<div class="custom-control custom-radio">
					<input type="radio" name="customRadio" class="custom-control-input" id="r1">
					<label class="custom-control-label" for="r1">Spam or excessive off-topic content</label>
				</div>
				<div class="custom-control custom-radio">
					<input type="radio" name="customRadio" class="custom-control-input" id="r2">
					<label class="custom-control-label" for="r2">Abusive, harmful or illegal content</label>
				</div>
				<div class="custom-control custom-radio">
					<input type="radio" name="customRadio" class="custom-control-input" id="r2">
					<label class="custom-control-label" for="r2">Impersonation</label>
				</div>
				<div class="custom-control custom-radio">
					<input type="radio" name="customRadio" class="custom-control-input" id="r2">
					<label class="custom-control-label" for="r2">Not moderated enough or ignores moderation reports</label>
				</div>
				<div class="custom-control custom-radio">
					<input type="radio" name="customRadio" class="custom-control-input" id="r2">
					<label class="custom-control-label" for="r2">Other</label>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection
