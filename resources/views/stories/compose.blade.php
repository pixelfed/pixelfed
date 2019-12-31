@extends('layouts.blank')


@section('content')
<story-compose></story-compose>
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/story-compose.js') }}"></script>
<script type="text/javascript">window.App.boot()</script>
@endpush