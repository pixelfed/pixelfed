@extends('layouts.app')

@section('content')
<remote-auth-start-component :config='{!!\App\Services\Account\RemoteAuthService::getConfig()!!}'/>
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/remote_auth.js')}}"></script>
<script type="text/javascript">App.boot();</script>
@endpush
