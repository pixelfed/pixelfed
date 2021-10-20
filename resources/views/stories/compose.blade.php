@extends('layouts.blank')


@section('content')
<story-compose profile-id="{{auth()->user()->profile_id}}"></story-compose>
@endsection

@push('scripts')
<script type="text/javascript" src="/js/story-compose.js?v={{time()}}"></script>
<script type="text/javascript">window.App.boot()</script>
@endpush
