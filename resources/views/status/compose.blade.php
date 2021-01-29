@extends('layouts.app')

@section('content')

<div class="container">
	<div class="row">
		<div class="col-12 col-md-6 offset-md-3 mt-md-3 px-0">
			<compose-modal></compose-modal>
		</div>
	</div>
</div>
@endsection

@push('styles')
<style type="text/css">
	.card {
		box-shadow: none;
		border: 1px solid #ddd;
	}
	.card .card-header .fas.fa-times {
		color: #fff;
	}
</style>
@endpush

@push('scripts')
<script type="text/javascript" src="{{ mix('js/compose.js') }}"></script>
<script type="text/javascript">window.App.boot()</script>
@endpush