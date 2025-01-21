@extends('layouts.app')

@section('content')
<group-profile pg="{{$group}}" pp="{{$profile}}" />
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/groups.js') }}"></script>
<script type="text/javascript">App.boot();</script>
@endpush
