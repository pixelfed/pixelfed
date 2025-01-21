@extends('settings.template')

@section('section')

<div class="title">
	<h3 class="font-weight-bold">{{__('settings.developers')}}</h3>
</div>
<hr>
@if((bool) config_cache('pixelfed.oauth_enabled') == true)
	<passport-clients></passport-clients>
@else
	<p class="lead">{{__('settings.developers.oauth_has_not_been_enabled_on_this_instance')}}</p>
@endif

@endsection

@push('scripts')
<script type="text/javascript" src="{{mix('js/developers.js')}}"></script>
@endpush
