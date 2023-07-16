@extends('layouts.app')

@section('content')
<remote-auth-start-component />
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/remote_auth.js')}}"></script>
<script type="text/javascript">App.boot();</script>
@endpush
