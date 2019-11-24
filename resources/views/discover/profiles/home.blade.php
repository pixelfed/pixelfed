@extends('layouts.app')

@section('content')

<div class="container mt-5">	
	<div class="col-12">
		<profile-directory profile-id="{{Auth::user()->profile_id}}"></profile-directory>
	</div>
</div>

@endsection

@push('scripts')
<script type="text/javascript" src="{{mix('js/compose.js')}}"></script>
<script type="text/javascript" src="{{mix('js/profile-directory.js')}}"></script>
<script type="text/javascript">App.boot();</script>
@endpush