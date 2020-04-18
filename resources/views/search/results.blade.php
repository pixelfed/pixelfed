@extends('layouts.app')

@section('content')
	<search-results query="{{request()->query('q')}}" profile-id="{{Auth::user()->profile->id}}"></search-results>
@endsection


@push('scripts')
<script type="text/javascript" src="{{mix('js/compose.js')}}"></script>
<script type="text/javascript" src="{{mix('js/search.js')}}"></script>
<script type="text/javascript">App.boot();</script>
@endpush