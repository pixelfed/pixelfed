@extends('layouts.spa')

@section('content')
<group-feed group-id="{{$id}}" path="{{$path}}" />
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/groups.js') }}"></script>
<script type="text/javascript">App.boot();</script>
@endpush
