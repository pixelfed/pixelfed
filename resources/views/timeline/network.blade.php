@extends('layouts.app')

@section('content')

<network-timeline></network-timeline>

@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/network-timeline.js') }}"></script>
<script type="text/javascript" src="{{ mix('js/compose.js') }}"></script>
<script type="text/javascript">window.App.boot();</script>
@endpush
