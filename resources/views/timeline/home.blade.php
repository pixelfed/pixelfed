@extends('layouts.app')

@section('content')

<timeline scope="home" layout="feed"></timeline>

@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/timeline.js') }}"></script>
<script type="text/javascript" src="{{ mix('js/compose.js') }}"></script>
<script type="text/javascript">window.App.boot()</script>
@endpush