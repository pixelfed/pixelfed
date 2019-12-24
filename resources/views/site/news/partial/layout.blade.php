@extends('layouts.anon')

@section('content')
 @include('site.news.partial.nav')
 @yield('body');
@endsection

@push('styles')
<style type="text/css">
	html, body {
		background: #fff;
	}
	.navbar-laravel {
		box-shadow: none;
	}
</style>
@endpush