@extends('layouts.blank')


@section('content')
<story-compose profile-id="{{auth()->user()->profile_id}}"></story-compose>
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/story-compose.js') }}"></script>
<script type="text/javascript">window.App.boot()</script>
@endpush