@extends('layouts.blank')

@section('content')
<admin-invite code="{{$code}}" />
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/admin_invite.js') }}"></script>
<script type="text/javascript">App.boot();</script>
@endpush

@push('styles')
<link href="{{ mix('css/spa.css') }}" rel="stylesheet" data-stylesheet="light">
<style type="text/css">
    body {
        background: #4776E6;
        background: -webkit-linear-gradient(to right, #8E54E9, #4776E6);
        background: linear-gradient(to right, #8E54E9, #4776E6);
    }
</style>
@endpush
