@extends('layouts.blank')

@section('content')
<story-viewer pid="{{$pid}}" redirect-url="/"></story-viewer>
@endsection

@push('scripts')
<script type="text/javascript" src="/js/stories.js?v={{ time() }}"></script>
<script type="text/javascript">App.boot();</script>
@endpush
