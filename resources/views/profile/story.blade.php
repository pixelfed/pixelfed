@extends('layouts.app')

@section('content')
<story-viewer pid="{{$pid}}" redirect="{{$profile->url()}}"></story-viewer>
@endsection

@push('scripts')
<script type="text/javascript" src="{{mix('js/compose.js')}}"></script>
<script type="text/javascript" src="{{mix('js/profile.js')}}"></script>
<script type="text/javascript">App.boot();</script>
@endpush