@extends('settings.template')

@section('section')

<div class="title">
	<h3 class="font-weight-bold">Applications</h3>
</div>
<hr>
@if(config('pixelfed.oauth_enabled') == true)
	<passport-authorized-clients></passport-authorized-clients>
	<passport-personal-access-tokens></passport-personal-access-tokens>
@else
	<p class="lead">OAuth has not been enabled on this instance.</p>
@endif
@endsection

@push('scripts')
<script type="text/javascript" src="{{mix('js/developers.js')}}"></script>
@endpush