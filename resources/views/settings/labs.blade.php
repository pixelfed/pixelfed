@extends('settings.template')

	@section('section')
	<div class="title">
		<h3 class="font-weight-bold">{{__('settings.labs')}}</h3>
		<p class="lead">{{__('settings.labs.experimental_features')}}</p>
	</div>
	<hr>
	<form method="post" id="form">
		@csrf
		<div class="form-group row">
			<div class="col-12">
				<button type="submit" class="btn btn-primary font-weight-bold py-1 btn-block">{{__('settings.save')}}</button>
			</div>
		</div>
	</form>
	@endsection
