@extends('admin.partial.template-full')

@section('section')
<div class="title">
	<h3 class="font-weight-bold d-inline-block">Discover</h3>
	<p class="lead">Create Category</p>
</div>

<hr>

<form class="px-md-5 cc-form" method="post">
	<div class="form-group row">
		<label for="categoryName" class="col-sm-2 col-form-label font-weight-bold">Name</label>
		<div class="col-sm-10">
			<input type="text" class="form-control" id="categoryName" placeholder="Nature" autocomplete="off">
			<p class="form-text small font-weight-bold text-muted">Slug: /discover/c/nature</p>
		</div>
	</div>
	<div class="form-group row">
		<label for="categoryName" class="col-sm-2 col-form-label font-weight-bold">Media ID</label>
		<div class="col-sm-10">
			<input type="text" class="form-control" id="categoryMedia" placeholder="1" autocomplete="off">
			<p class="form-text small font-weight-bold text-muted">Media ID is used for category thumbnail image</p>
		</div>
	</div>
	<div class="form-group row">
		<label for="categoryActive" class="col-sm-2 col-form-label font-weight-bold">Active</label>
		<div class="col-sm-10">
			<div class="custom-control custom-switch pt-2">
				<input type="checkbox" class="custom-control-input" id="categoryActive">
				<label class="custom-control-label" for="categoryActive"></label>
			</div>
		</div>
	</div>

	<div class="form-group row">
		<label class="col-sm-2 col-form-label font-weight-bold">Rules</label>
		<div class="col-sm-10">
			<div class="custom-control custom-switch pt-2">
				<input type="checkbox" class="custom-control-input" id="categoryNsfw">
				<label class="custom-control-label" for="categoryNsfw">Allow NSFW</label>
			</div>
			<div class="custom-control custom-switch pt-2">
				<input type="checkbox" class="custom-control-input" id="categoryNsfw">
				<label class="custom-control-label" for="categoryType">Allow Photos + Video</label>
			</div>
		</div>
	</div>
	<hr>
	<div class="form-group">
		<div class="text-right">
			<button type="submit" class="btn btn-primary btn-sm py-1 font-weight-bold">Create</button>
		</div>
	</div>
</form>

@endsection

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {
	$('.cc-form').on('submit', function(e) {
		e.preventDefault();
		let data = {
			'name': document.getElementById('categoryName').value,
			'media': document.getElementById('categoryMedia').value,
			'active': document.getElementById('categoryActive').checked
		};

		axios.post('{{request()->url()}}', data)
		.then(res => {
			window.location.href = '{{route('admin.discover')}}';
		});
	})
});
</script>
@endpush