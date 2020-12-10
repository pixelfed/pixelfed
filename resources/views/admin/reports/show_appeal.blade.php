@extends('admin.partial.template-full')

@section('section')
<div class="d-flex justify-content-between title mb-3">
	<div>
		<p class="font-weight-bold h3">Moderation Appeal</p>
		<p class="text-muted mb-0 lead">From <a href="{{$appeal->user->url()}}" class="text-muted font-weight-bold">&commat;{{$appeal->user->username}}</a> about {{$appeal->appeal_requested_at->diffForHumans()}}.</p>
	</div>
	<div>
	</div>
</div>
<div class="row">
	<div class="col-12 col-md-8 mt-3">
		@if($appeal->type == 'post.cw')
		<div class="card shadow-none border">
			<div class="card-header bg-light h5 font-weight-bold py-4">Content Warning applied to {{$appeal->has_media ? 'Post' : 'Comment'}}</div>
			@if($appeal->has_media)
			<img class="card-img-top border-bottom" src="{{$appeal->status->thumb(true)}}">
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
					<p class="mb-0">
						Message: <span class="font-weight-bold">{{$appeal->appeal_message}}</span>
					</p>
				</div>
			</div>
		</div>
		@elseif($appeal->type == 'post.unlist')
		<div class="card shadow-none border">
			<div class="card-header bg-light h5 font-weight-bold py-4">{{$appeal->has_media ? 'Post' : 'Comment'}} was unlisted from timelines</div>
			@if($appeal->has_media)
			<img class="card-img-top border-bottom" src="{{$appeal->status->thumb(true)}}">
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
					<p class="mb-0">
						Message: <span class="font-weight-bold">{{$appeal->appeal_message}}</span>
					</p>
				</div>
			</div>
		</div>
		@endif
	</div>
	<div class="col-12 col-md-4 mt-3">
		<form method="post">
			@csrf
			<input type="hidden" name="action" value="dismiss">
			<button type="submit" class="btn btn-primary btn-block font-weight-bold mb-3">Dismiss Appeal Request</button>
		</form>
		<button type="button" class="btn btn-light border btn-block font-weight-bold mb-3" onclick="approveWarning()">Approve Appeal</button>
		<div class="card shadow-none border mt-5">
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
	function approveWarning() {
		if(window.confirm('Are you sure you want to approve this appeal?') == true) {
			axios.post(window.location.href,  {
				action: 'approve'
			}).then(res => {
				window.location.href = '/i/admin/reports/appeals';
			}).catch(err => {
				swal('Oops!', 'An error occured, please try again later.', 'error');
			});
		}
	}
</script>
@endpush