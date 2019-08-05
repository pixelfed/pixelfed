@extends('layouts.app')

@section('content')
<div class="container">
<discover-component></discover-component>
</div>
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/discover.js') }}"></script>
<script type="text/javascript" src="{{ mix('js/compose.js') }}"></script>
<script type="text/javascript">App.boot();</script>
@endpush