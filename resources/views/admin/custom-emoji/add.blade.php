@extends('admin.partial.template-full')

@section('section')
</div>
<div class="header bg-primary pb-3 mt-n4">
	<div class="container-fluid">
		<div class="header-body">
			<div class="row align-items-center py-4">
				<div class="col-lg-6 col-7">
					<p class="display-1 text-white d-inline-block mb-0">Add Custom Emoji</p>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="container mt-5">
	<div class="row justify-content-center">
		<div class="col-12 col-md-6">
		@if ($errors->any())
			@foreach ($errors->all() as $error)
			<div class="alert alert-danger py-2 {{$loop->last?'mb-4':'mb-2'}}">
				<p class="mb-0"><i class="far fa-exclamation-triangle mr-2"></i> {{ $error }}</p>
			</div>
			@endforeach
		@endif

			<div class="card">
				<div class="card-header font-weight-bold">
					New Custom Emoji
				</div>

				<div class="card-body">
					<form method="post" enctype="multipart/form-data">
						@csrf
						<div class="form-group">
							<label for="shortcode" class="font-weight-light">Shortcode</label>
							<input class="form-control" id="shortcode" name="shortcode" placeholder=":pixelfed:" required>
							<p class="form-text small font-weight-bold">Must start and end with :</p>
						</div>

						<div class="form-group">
							<label for="media" class="font-weight-light">Emoji Image</label>
							<input type="file" class="form-control-file" id="media" name="emoji" required>
							<p class="form-text font-weight-bold"><span class="small">Must be a <kbd>png</kbd> or <kbd>jpg</kbd> under</span> <span class="badge badge-info filesize" data-filesize="{{config('federation.custom_emoji.max_size')}}"></span></p>
						</div>
						<hr>
						<button class="btn btn-primary btn-block">Add Emoji</button>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
	$('.filesize').each(function(el, i) {
		let size = filesize($(i).data('filesize'));
		i.innerText = size;
	})
</script>
@endpush
