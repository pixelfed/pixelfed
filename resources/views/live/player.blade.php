@extends('layouts.blank')

@section('content')
<div class="force-dark-mode">
    <live-player id="{{ $id }}"></live-player>
</div>
@endsection

@push('scripts')
<script type="text/javascript" src="/js/live-player.js?v={{ time() }}"></script>
<script type="text/javascript">App.boot();</script>
@endpush

@push('meta')
<script type="text/javascript">
    window._pushr = {
        host: "{{ config('broadcasting.connections.pusher.options.host')}}",
        port: "{{ config('broadcasting.connections.pusher.options.port')}}",
        key: "{{ config('broadcasting.connections.pusher.key')}}",
        cluster: "{{ config('broadcasting.connections.pusher.options.cluster')}}"
    };
</script>
@endpush

@push('styles')
<link rel="stylesheet" type="text/css" href="{{ mix('css/spa.css') }}">
<style type="text/css">
body {
    background-color: #000000;
    background-image: radial-gradient(circle, #0f172a 0%, #000000 74%);
}
</style>
@endpush
