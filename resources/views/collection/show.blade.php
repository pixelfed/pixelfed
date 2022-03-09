@extends('layouts.app')

@section('content')

<div class="container">
	<collection-component 
		collection-id="{{$collection['id']}}"
		collection-title="{{$collection['title']}}"
		collection-description="{{$collection['description']}}"
		collection-visibility="{{$collection['visibility']}}"
		profile-id="{{$collection['pid']}}"
		profile-username="{{$collection['username']}}"
	></collection-component>
</div>

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
<script type="text/javascript" src="{{mix('js/compose.js')}}" async></script>
	<script type="text/javascript" src="{{mix('js/collections.js')}}"></script>
	<script type="text/javascript">App.boot()</script>
@endpush	
