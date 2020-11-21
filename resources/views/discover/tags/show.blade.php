@extends('layouts.app')

@section('content')
<hashtag-component hashtag="{{$tag->name}}" hashtag-count="{{$tagCount}}"></hashtag-component>
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/hashtag.js') }}"></script>
<script type="text/javascript" src="{{ mix('js/compose.js') }}"></script>
<script type="text/javascript">App.boot();</script>
@endpush