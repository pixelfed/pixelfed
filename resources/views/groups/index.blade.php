@extends('layouts.app')

@section('content')
<groups-home />
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/groups.js') }}"></script>
<script type="text/javascript">App.boot();</script>
@endpush
