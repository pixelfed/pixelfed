@extends('layouts.app')

@section('content')

<noscript>
	<div class="container">
		<p class="pt-5 text-center lead">Please enable javascript to view this content.</p>
	</div>
</noscript>

@if(request()->has('l') && request()->input('l') == 'grid')
<timeline scope="local" layout="grid"></timeline>
@else
<timeline scope="local" layout="feed"></timeline>
@endif

<div class="modal pr-0" tabindex="-1" role="dialog" id="composeModal">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<compose-modal></compose-modal>
		</div>
	</div>
</div>

@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/timeline.js') }}"></script>
<script type="text/javascript" src="{{ mix('js/compose.js') }}"></script>
<script type="text/javascript">window.App.boot()</script>
@endpush