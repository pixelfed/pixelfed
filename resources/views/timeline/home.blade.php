@extends('layouts.app')
{{-- @extends('layouts.blank') --}}

@section('content')
@if(session('statusRedirect'))
<div class="alert alert-warning border-bottom">
	<div class="row">
		<div class="col-2">
			<p class="mb-0"></p>
		</div>
		<div class="col-8">
			<p class="font-weight-bold text-center mb-0">
				{{ session('statusRedirect') }}
			</p>
		</div>
		<div class="col-2 cursor-pointer" onclick="this.parentNode.parentNode.style.display='none'">
			<p class="mb-0">
				<i class="fas fa-times"></i>
			</p>
		</div>
	</div>
</div>
@endif
<noscript>
	<div class="container">
		<p class="pt-5 text-center lead">Please enable javascript to view this content.</p>
	</div>
</noscript>

<timeline scope="home" layout="feed"></timeline>
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
