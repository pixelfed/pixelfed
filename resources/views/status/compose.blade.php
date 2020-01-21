@extends('layouts.app')

@section('content')

<div class="compose-container container mt-5 d-none">
	<div class="row">
		<div class="col-12 col-md-6 offset-md-3">
			<div class="alert alert-info text-center">
				<span class="font-weight-bold">ComposeUI v3 is deprecated</span>
				<br>
				It will be removed after June 1st, 2020
			</div>
			<p class="lead text-center">
				<a href="javascript:void(0)" class="btn btn-primary font-weight-bold" data-toggle="modal" data-target="#composeModal">Compose New Post</a>
			</p>
		</div>
	</div>
</div>

<div class="modal pr-0" tabindex="-1" role="dialog" id="composeModal">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<compose-classic></compose-classic>
		</div>
	</div>
</div>

@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/compose-classic.js') }}"></script>
	<script type="text/javascript">
		App.boot();
		$('#composeModal').modal('show');
		$('.compose-container').removeClass('d-none');
	</script>

@endpush