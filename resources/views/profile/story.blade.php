@extends('layouts.blank')

@section('content')
<story-viewer pid="{{$pid}}" redirect-url="{{$profile->url()}}"></story-viewer>
@endsection

@push('scripts')
<script type="text/javascript" src="{{mix('js/stories.js')}}"></script>
<script type="text/javascript" src="{{mix('js/profile.js')}}"></script>
<script type="text/javascript">App.boot();</script>
@endpush
