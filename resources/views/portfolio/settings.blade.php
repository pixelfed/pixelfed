@extends('portfolio.layout')

@section('content')
<div class="container">
	<div class="row mt-5 pt-5 px-0 align-items-center">
		<div class="col-12 mb-5 col-md-8">
			<span class="logo-mark px-3"><span class="text-gradient-primary">portfolio</span></span>
		</div>
        <div class="col-12 mb-5 col-md-4 text-md-right">
            <h1 class="font-weight-bold">Settings</h1>
        </div>
	</div>

    <portfolio-settings />
</div>
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/portfolio.js') }}"></script>
<script type="text/javascript">
    App.boot();
</script>
@endpush
