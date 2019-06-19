@extends('admin.partial.template-full')

@section('section')
<div class="title">
	<div class="d-flex justify-content-between align-items-center">
		<div class="font-weight-bold"># {{$message->id}}</div>
		<div class="font-weight-bold h3">Message</div>
		<div>
			@if($message->read_at)
			<span class="btn btn-outline-secondary btn-sm disabled" disabled>Read</span>
			@else
			<button type="button" class="btn btn-outline-primary btn-sm" id="markRead">Mark Read</button>
			@endif
		</div>
	</div>
</div>

<hr>

@endsection

@push('scripts')
<script type="text/javascript">
	$('#markRead').on('click', function(e) {
		e.preventDefault();

		axios.post('/i/admin/messages/mark-read', {
			id: '{{$message->id}}',
		}).then(res => {
			window.location.href = window.location.href;
		})	
	})
</script>
@endpush