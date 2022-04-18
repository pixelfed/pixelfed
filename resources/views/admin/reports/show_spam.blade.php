@extends('admin.partial.template-full')

@section('section')
<div class="d-flex justify-content-between title mb-3">
	<div>
		<p class="font-weight-bold h3">Autospam</p>
		<p class="text-muted mb-0 lead">Detected <span class="font-weight-bold">{{$appeal->created_at->diffForHumans()}}</span> from <a href="{{$appeal->user->url()}}" class="text-muted font-weight-bold">&commat;{{$appeal->user->username}}</a>.</p>
	</div>
	<div>
	</div>
</div>
<div class="row">
	<div class="col-12 col-md-8 mt-3">
		@if($appeal->type == 'post.autospam')
		<div class="card shadow-none border">
			<div class="card-header bg-light h5 font-weight-bold py-4">Unlisted + Content Warning</div>
			@if($appeal->has_media)
			<img class="card-img-top border-bottom" src="{{$appeal->status->thumb(true)}}" style="max-height: 40vh;object-fit: contain;">
			@endif
			<div class="card-body">
				<div class="mt-2 p-3">
					@if($meta->caption)
					<p class="text-break">
						{{$appeal->has_media ? 'Caption' : 'Comment'}}: <span class="font-weight-bold">{{$meta->caption}}</span>
					</p>
					@endif
					<p class="mb-0">
						Like Count: <span class="font-weight-bold">{{$meta->likes_count}}</span>
					</p>
					<p class="mb-0">
						Share Count: <span class="font-weight-bold">{{$meta->reblogs_count}}</span>
					</p>
					<p class="mb-0">
						Timestamp: <span class="font-weight-bold">{{now()->parse($meta->created_at)->format('r')}}</span>
					</p>
					<p class="" style="word-break: break-all !important;">
						URL: <span class="font-weight-bold text-primary"><a href="{{$meta->url}}">{{$meta->url}}</a></span>
					</p>
				</div>
			</div>
		</div>
		@endif
	</div>
	<div class="col-12 col-md-4 mt-3">
		@if($appeal->appeal_handled_at)
		@else
		<button type="button" class="btn btn-primary border btn-block font-weight-bold mb-3 action-btn" data-action="dismiss">Mark as read</button>
		<button type="button" class="btn btn-light border btn-block font-weight-bold mb-3 action-btn" data-action="approve">Mark as not spam</button>
		<hr>
		<button type="button" class="btn btn-default border btn-block font-weight-bold mb-3 action-btn" data-action="dismiss-all">Mark all as read</button>
		<button type="button" class="btn btn-light border btn-block font-weight-bold mb-3 action-btn" data-action="approve-all">Mark all as not spam</button>
		<button type="button" class="btn btn-danger border btn-block font-weight-bold mb-3 action-btn mb-5" data-action="delete-account">Delete Account</button>
		@endif
		<div class="card shadow-none border">
			<div class="card-header text-center font-weight-bold bg-light">
				&commat;{{$appeal->user->username}} stats
			</div>
			<div class="card-body">
				<p class="">
					Open Appeals: <span class="font-weight-bold">{{App\AccountInterstitial::whereUserId($appeal->user_id)->whereNotNull('appeal_requested_at')->whereNull('appeal_handled_at')->count()}}</span>
				</p>
				<p class="">
					Total Appeals: <span class="font-weight-bold">{{App\AccountInterstitial::whereUserId($appeal->user_id)->whereNotNull('appeal_requested_at')->count()}}</span>
				</p>
				<p class="">
					Total Warnings: <span class="font-weight-bold">{{App\AccountInterstitial::whereUserId($appeal->user_id)->count()}}</span>
				</p>
				<p class="">
					Status Count: <span class="font-weight-bold">{{$appeal->user->statuses()->count()}}</span>
				</p>
				<p class="mb-0">
					Joined: <span class="font-weight-bold">{{$appeal->user->created_at->diffForHumans(null, null, false)}}</span>
				</p>
			</div>
		</div>
	</div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
	$('.action-btn').click((e) => {
		e.preventDefault();
		e.currentTarget.blur();

		let type = e.currentTarget.getAttribute('data-action');

		switch(type) {
			case 'dismiss':
			break;

			case 'approve':
				if(!window.confirm('Are you sure you want to approve this post?')) {
					return;
				}
			break;

			case 'dismiss-all':
				if(!window.confirm('Are you sure you want to dismiss all autospam reports?')) {
					return;
				}
			break;

			case 'approve-all':
				if(!window.confirm('Are you sure you want to approve this post and all other posts by this account?')) {
					return;
				}
			break;

			case 'delete-account':
				if(!window.confirm('Are you sure you want to delete this account?')) {
					return;
				}
			break;
		}

		axios.post(window.location.href, {
			action: type
		}).then(res => {
			location.href = '/i/admin/reports/autospam';
		}).catch(err => {
			swal('Oops!', 'An error occured', 'error');
			console.log(err);
		})
	});
</script>
@endpush
