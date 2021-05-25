@extends('layouts.admin')

@section('content')

@include('admin.partial.sidenav')
<div class="main-content" id="panel">
	@include('admin.partial.nav')
	<div class="container-fluid mt-4">
		@yield('section')
	</div>
</div>
@endsection
