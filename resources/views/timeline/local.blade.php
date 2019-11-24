@extends('layouts.app')

@section('content')

<timeline scope="local" layout="feed"></timeline>

@endsection

@if($layout == 'grid')
@push('styles')
<style type="text/css">
	body {
		background: #fff !important;
	}
	.navbar.border-bottom {
		border-bottom: none !important;
	}
</style>
@endpush
@endif

@push('scripts')
<script type="text/javascript" src="{{ mix('js/timeline.js') }}"></script>
<script type="text/javascript" src="{{ mix('js/compose.js') }}"></script>
<script type="text/javascript">
	new Vue({
		el: '#content'
	});
</script>
@endpush