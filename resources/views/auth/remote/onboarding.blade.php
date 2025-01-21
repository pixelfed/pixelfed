@extends('layouts.app')

@section('content')
<remote-auth-getting-started-component />
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/remote_auth.js')}}"></script>
<script type="text/javascript">App.boot();</script>
@endpush
