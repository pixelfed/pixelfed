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

<div class="row">
	
	<div class="col-12 col-md-3 text-md-right">
		@if($message->response_requested)
		<p class="text-dark font-weight-bold">Response Requested</p>
		@endif
		<p class="text-dark">Sent {{$message->created_at->diffForHumans()}}</p>
	</div>
	<div class="col-12 col-md-6">
		
		<div class="card shadow-none border">
			<div class="card-header bg-white">
				<div class="media">
					<img src="{{$message->user->profile->avatarUrl()}}" class="mr-3 rounded-circle" width="40px" height="40px">
					<div class="media-body">
						<h5 class="my-0">&commat;{{$message->user->username}}</h5>
						<span class="text-muted">{{$message->user->email}}</span>
					</div>
				</div>
			</div>
			<div class="card-body">
				<p class="mb-0">{{$message->message}}</p>
			</div>
		</div>
	</div>
	<div class="col-12 col-md-3">
		{{-- @if($message->responded_at == null)
		<button class="btn btn-primary font-weight-bold btn-block">Send Response</button>
		<hr>
		@endif
		<button class="btn btn-outline-danger font-weight-bold btn-block">Delete</button> --}}
	</div>
</div>

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