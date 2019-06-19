@extends('layouts.app')

@section('content')

<div class="container mt-5">
	<div class="row">
		<div class="col-12 col-md-6 offset-md-3">
      <p class="lead text-center font-weight-bold">The Classic Compose UI has been retired.</p>
      <p class="lead text-center">
        <a href="javascript:void(0)" class="btn btn-primary font-weight-bold" data-toggle="modal" data-target="#composeModal">New Post</a>
      </p>
		</div>
	</div>
</div>

@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/compose.js') }}"></script>
<script type="text/javascript">
$(document).ready(function() {
  new Vue({ 
    el: '#content'
  });
});
</script>
@endpush