@extends('admin.partial.template')

@include('admin.settings.sidebar')

@section('section')
<div class="title mb-4">
	<h3 class="font-weight-bold">Settings</h3>
	<p class="lead">Manage instance settings</p>
</div>
<form method="post">
	@csrf
	<div class="form-group mb-0">
		<div class="ml-n4 mr-n2 p-3 bg-light border-top border-bottom">
			<label class="font-weight-bold text-muted">Name</label>
			<input class="form-control col-8" name="name" placeholder="Pixelfed" value="{{$name}}">
			<p class="help-text small text-muted mt-3 mb-0">The instance name used in titles, metadata and apis.</p>
		</div>
	</div>
	<div class="form-group mb-0">
		<div class="ml-n4 mr-n2 p-3 bg-light border-bottom">
			<label class="font-weight-bold text-muted">Short Description</label>
			<textarea class="form-control" rows="3" name="short_description">{{$short_description}}</textarea>
			<p class="help-text small text-muted mt-3 mb-0">Short description of instance used on various pages and apis.</p>
		</div>
	</div>
	<div class="form-group mb-0">
		<div class="ml-n4 mr-n2 p-3 bg-light border-bottom">
			<label class="font-weight-bold text-muted">Long Description</label>
			<textarea class="form-control" rows="3" name="long_description">{{$description}}</textarea>
			<p class="help-text small text-muted mt-3 mb-0">Longer description of instance used on about page.</p>
		</div>
	</div>


	<div class="form-group row mb-0 mt-4">
		<div class="col-12 text-right">
			<button type="submit" class="btn btn-primary font-weight-bold px-5">Save</button>
		</div>
	</div>
</form>
@endsection
