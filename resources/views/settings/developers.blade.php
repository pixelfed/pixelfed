@extends('settings.template')

@section('section')

<div class="title">
	<h3 class="font-weight-bold">Developers</h3>
</div>
<hr>
@if(config_cache('pixelfed.oauth_enabled') == true)
	<passport-clients></passport-clients>
@else
	<p class="lead">OAuth has not been enabled on this instance.</p>
@endif

@endsection

@push('scripts')
<script type="text/javascript" src="{{mix('js/developers.js')}}"></script>
@endpush
