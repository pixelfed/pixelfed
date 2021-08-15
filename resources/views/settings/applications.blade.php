@extends('settings.template')

@section('section')

<div class="title">
	<h3 class="font-weight-bold">{{__('settings.applications')}}</h3>
</div>
<hr>
@if(config_cache('pixelfed.oauth_enabled') == true)
	<passport-authorized-clients></passport-authorized-clients>
	<passport-personal-access-tokens></passport-personal-access-tokens>
@else
	<p class="lead">{{__('settings.oathNotEnabled')}}</p>
@endif
@endsection

@push('scripts')
<script type="text/javascript" src="{{mix('js/developers.js')}}"></script>
@endpush
