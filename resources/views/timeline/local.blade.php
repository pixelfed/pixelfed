@extends('layouts.app')

@section('content')

<timeline scope="local" layout="feed"></timeline>

@endsection

@push('styles')
<style type="text/css">
	body {
		background: #fff !important;
	}
	.navbar {
		border: none !important;
	}
</style>
@endpush

@push('scripts')
<script type="text/javascript" src="{{ mix('js/timeline.js') }}"></script>
<script type="text/javascript" src="{{ mix('js/compose.js') }}"></script>
<script type="text/javascript">
	new Vue({
		el: '#content'
	});
</script>
@endpush