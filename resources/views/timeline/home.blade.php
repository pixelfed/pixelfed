@extends('layouts.app')

@section('content')

<timeline scope="home" layout="feed"></timeline>

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
<script type="text/javascript">window.App.boot()</script>
@endpush