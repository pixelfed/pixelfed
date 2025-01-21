@extends('layouts.app')

@section('content')
<group-topic-feed gid="{{$gid}}" name="{{$tag}}" />
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/group-topic-feed.js') }}"></script>
<script type="text/javascript">App.boot();</script>
@endpush
