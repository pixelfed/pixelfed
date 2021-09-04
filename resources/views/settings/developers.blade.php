@extends('settings.template')

@section('section')

<div class="title">
	<h3 class="font-weight-bold">{{__('settings.developers')}}</h3>
</div>
<hr>
@if(config_cache('pixelfed.oauth_enabled') == true)
	<passport-clients></passport-clients>
@else
	<p class="lead">{{__('settings.oathNotEnabled')}}</p>
@endif

@endsection

@push('scripts')
<script type="text/javascript" src="{{mix('js/developers.js')}}"></script>
@endpush
