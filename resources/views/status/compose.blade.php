@extends('layouts.app')

@section('content')

<div class="container mt-5">
	<div class="row">
		<div class="col-12 col-md-6 offset-md-3">
			@include('timeline.partial.new-form')
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