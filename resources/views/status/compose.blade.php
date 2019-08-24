@extends('layouts.app')

@section('content')

{{-- <div class="container mt-5">
	<div class="row">
		<div class="col-12 col-md-6 offset-md-3">
      <p class="lead text-center font-weight-bold">Compose New Post</p>
      <p class="lead text-center">
        <a href="javascript:void(0)" class="btn btn-primary font-weight-bold" data-toggle="modal" data-target="#composeModal">New Post</a>
      </p>
		</div>
	</div>
</div> --}}

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
<script type="text/javascript">App.boot();</script>
<script type="text/javascript">
    $('#composeModal').modal('show');
</script>
@endpush