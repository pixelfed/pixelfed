@extends('admin.partial.template-full')

@section('section')
<div class="title d-flex justify-content-between align-items-center">
	<span><a href="/i/admin/users/show/{{$user->id}}" class="btn btn-outline-secondary btn-sm font-weight-bold">Back</a></span>
	<span class="text-center">
		<h3 class="font-weight-bold mb-0">&commat;{{$profile->username}}</h3>
		<p class="mb-0 small text-muted text-uppercase font-weight-bold">
			<span>{{$profile->statuses()->count()}} Posts</span>
			<span class="px-1">|</span>
			<span>{{$profile->followers()->count()}} Followers</span>
			<span class="px-1">|</span>
			<span>{{$profile->following()->count()}} Following</span>
		</p>
	</span>
	<span>
		<div class="dropdown">
			<button class="btn btn-outline-secondary btn-sm font-weight-bold dropdown-toggle" type="button" id="userActions" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-bars"></i></button>
			<div class="dropdown-menu dropdown-menu-right" aria-labelledby="userActions">
				<a class="dropdown-item" href="/i/admin/users/show/{{$user->id}}">
					<span class="font-weight-bold">Overview</span>
				</a>
				<a class="dropdown-item" href="{{$profile->url()}}">
					<span class="font-weight-bold">View Profile</span>
				</a>
				<div class="dropdown-divider"></div>
				<a class="dropdown-item" href="/i/admin/users/edit/{{$user->id}}">
					<span class="font-weight-bold">Edit</span>
				</a>
				<a class="dropdown-item" href="/i/admin/users/modtools/{{$user->id}}">
					<span class="font-weight-bold">Mod Tools</span>
				</a>
				<a class="dropdown-item" href="/i/admin/users/modlogs/{{$user->id}}">
					<span class="font-weight-bold">Mod Logs</span>
				</a>
				<div class="dropdown-divider"></div>
				<a class="dropdown-item" href="/i/admin/users/delete/{{$user->id}}">
					<span class="text-danger font-weight-bold">Delete Account</span>
				</a>
			</div>
		</div>
	</span>
</div>
<hr>

<div class="row mb-3">
	<div class="col-12 col-md-8 offset-md-2">
		<p class="title h4 font-weight-bold mt-2 py-2">Send Message</p>
		<hr>
		<div class="row">
			<div class="col-12">
				@if ($errors->any())
				<div class="alert alert-danger">
					<ul>
						@foreach ($errors->all() as $error)
						<li>{{ $error }}</li>
						@endforeach
					</ul>
				</div>
				@endif
				<form method="post" id="messageForm">
					@csrf
					<div class="form-group">
						<textarea class="form-control" rows="8" placeholder="Message body ..." id="message" name="message"></textarea>
						<p class="help-text mb-0 small text-muted">
							<span>Plain text only, html will not be rendered.</span>
							<span class="float-right msg-counter"><span class="msg-count">0</span>/500</span>
						</p>
					</div>
					<p class="float-right">
						<button type="button" class="btn btn-primary py-1 font-weight-bold" onclick="submitWarning()"><i class="fas fa-message"></i> SEND</button>
					</p>
				</form>
			</div>
		</div>
	</div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
	$('#message').on('keyup change paste submit', function(e) {
		let len = e.target.value.length;
		$('.msg-count').text(len);
	});
	function submitWarning() {
		let msg = document.querySelector('#message');
		if(msg.value.length < 5) {
			swal('Oops!', 'Your message must be longer than 5 characters.', 'error');
			return;
		}
		if(msg.value.length > 500) {
			swal('Oops!', 'Your message must be shorter than 500 characters.', 'error');
			return;
		}
		swal({
			title: "Are you sure?",
			text: "Are you sure you want to send this message to {{$user->username}}?",
			icon: "warning",
			buttons: true,
			dangerMode: true,
		})
		.then((sendMessage) => {
			if (sendMessage) {
				$('#messageForm').submit();
			} else {
				return;
			}
		});
	}
</script>
@endpush