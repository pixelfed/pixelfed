@extends('layouts.app')

@section('content')
<div class="container">
  <div class="error-page py-5 my-5">
  	<div class="text-center">
		<h3 class="font-weight-bold">Something went wrong</h3>
		<p class="lead py-3">We cannot process your request at this time, please try again later. </p>
		<p class="mb-0">
			<a href="/" class="btn btn-primary font-weight-bold">Go back to timeline</a>
		</p>
  	</div>
  	@if($exception && $exception->getMessage())
  	<hr>
    <p class="text-center small payload text-uppercase font-weight-bold text-primary cursor-pointer" data-payload="{{encrypt('exception_report:'.$exception->getMessage()) ?? ''}}">
    	Copy diagnostic details
    </p>
    @endif
  </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
	let payload = document.querySelector('.payload').getAttribute('data-payload');

	$('.payload').click(function(e) {
		try {
			App.util.clipboard(payload);
			swal(
				'Copied',
				'The error details have been copied. Please share this with the administrators to help fix this issue.',
				'success'
			);
		} catch {
			swal(
				'Diagnostic Details',
				payload,
				'info'
			);
		}
	});
</script>
@endpush
