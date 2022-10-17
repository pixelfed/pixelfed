@extends('portfolio.layout')

@section('content')
<div class="container">
	<div class="row justify-content-center mt-5 pt-5">
		<div class="col-12 col-md-6 text-center">
			<p class="mb-3">
				<span class="logo-mark px-3"><span class="text-gradient-primary">portfolio</span></span>
			</p>

            <div class="spinner-border mt-5" role="status">
              <span class="sr-only">Loading...</span>
            </div>
		</div>

	</div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
	@auth
	axios.get('/api/v1/accounts/verify_credentials')
	.then(res => {
		if(res.data.locked == false) {
            window.location.href = 'https://{{ config('portfolio.domain') }}{{ config('portfolio.path') }}/' + res.data.username
		} else {
            window.location.href = "{{ config('app.url') }}";
		}
	})
    @else
        window.location.href = "{{ config('app.url') }}";
	@endauth

</script>
@endpush
