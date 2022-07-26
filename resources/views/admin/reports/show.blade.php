@extends('admin.partial.template-full')

@section('section')
	<div class="d-flex justify-content-between title mb-3">
		<div>
			<p class="font-weight-bold h3">
				Report #{{$report->id}} - 
				<span class="text-danger">{{ucfirst($report->type)}}</span>
			</p>
			<p class="text-muted mb-0 lead">
				Reported <span class="font-weight-bold">{{$report->created_at->diffForHumans()}}</span> by <a href="{{$report->reporter->url()}}" class="text-muted font-weight-bold">&commat;{{$report->reporter->username}}</a>.
			</p>
		</div>
	</div>

	<div class="row">
		<div class="col-12 col-md-8 mt-3">
			<div class="card shadow-none border">
				@if($report->status->media()->count())
				<img class="card-img-top border-bottom" src="{{$report->status->thumb(true)}}">
				@endif
				<div class="card-body">
					<div class="mt-2 p-3">
						@if($report->status->caption)
						<p class="text-break">
							{{$report->status->media()->count() ? 'Caption' : 'Comment'}}: <span class="font-weight-bold">{{$report->status->caption}}</span>
						</p>
						@endif
						<p class="mb-0">
							Like Count: <span class="font-weight-bold">{{$report->status->likes_count}}</span>
						</p>
						<p class="mb-0">
							Share Count: <span class="font-weight-bold">{{$report->status->reblogs_count}}</span>
						</p>
						<p class="mb-0">
							Timestamp: <span class="font-weight-bold">{{now()->parse($report->status->created_at)->format('r')}}</span>
						</p>
						<p class="mb-0" style="word-break: break-all !important;">
							Original URL: <span class="font-weight-bold text-primary"><a href="/i/web/post/{{$report->status->id}}">{{$report->status->url()}}</a></span>
						</p>
                        <p class="" style="word-break: break-all !important;">
                            Local URL: <span class="font-weight-bold text-primary"><a href="/i/web/post/{{$report->status->id}}">{{url('/i/web/post/' . $report->status->id)}}</a></span>
                        </p>
                        @if($report->status->in_reply_to_id)
                        <p class="mt-n3" style="word-break: break-all !important;">
                            Parent Post: <span class="font-weight-bold text-primary"><a href="/i/web/post/{{$report->status->in_reply_to_id}}">{{url('/i/web/post/' . $report->status->in_reply_to_id)}}</a></span>
                        </p>
                        @endif
					</div>
				</div>
			</div>
		</div>
		<div class="col-12 col-md-4 mt-3">
			<button type="button" class="btn btn-primary btn-block font-weight-bold mb-3 report-action-btn" data-action="cw">Apply Content Warning</button>
			<button type="button" class="btn btn-primary btn-block font-weight-bold mb-3 report-action-btn" data-action="unlist">Unlist Post</button>
			<button type="button" class="btn btn-light border btn-block font-weight-bold mb-3 report-action-btn" data-action="ignore">Ignore</button>

			<div class="card shadow-none border mt-5">
				<div class="card-header text-center font-weight-bold bg-light">
					{{$report->reportedUser->username}} stats
				</div>
				<div class="card-body">
					<p>
						Total Reports: <span class="font-weight-bold text-danger">{{App\Report::whereReportedProfileId($report->reportedUser->id)->count()}}</span>
					</p>
					<p>
						Total Warnings: <span class="font-weight-bold text-danger">{{App\AccountInterstitial::whereUserId($report->reportedUser->user_id)->count()}}</span>
					</p>
					<p class="">
						Status Count: <span class="font-weight-bold">{{$report->reportedUser->status_count}}</span>
					</p>
					<p class="">
						Follower Count: <span class="font-weight-bold">{{$report->reportedUser->followers_count}}</span>
					</p>
					<p class="mb-0">
						Joined: <span class="font-weight-bold">{{$report->reportedUser->created_at->diffForHumans(null, null, false)}}</span>
					</p>
				</div>
			</div>

			<div class="card shadow-none border mt-5">
				<div class="card-header text-center font-weight-bold bg-light">
					&commat;{{$report->reporter->username}} stats
				</div>
				<div class="card-body">
					<p class="">
						Status Count: <span class="font-weight-bold">{{$report->reporter->status_count}}</span>
					</p>
					<p class="">
						Follower Count: <span class="font-weight-bold">{{$report->reporter->followers_count}}</span>
					</p>
					<p class="mb-0">
						Joined: <span class="font-weight-bold">{{$report->reporter->created_at->diffForHumans(null, null, false)}}</span>
					</p>
				</div>
			</div>

		</div>
	</div>
@endsection

@push('scripts')
<script type="text/javascript">
	$(document).on('click', '.report-action-btn', function(e) {
		e.preventDefault();
		let el = $(this);
		let action = el.data('action');
		axios.post(window.location.href, {
			'action': action
		})
		.then(function(res) {
			swal('Success', 'Issue updated successfully!', 'success');
			window.location.href = '/i/admin/reports';
		}).catch(function(res) {
			swal('Error', res.data.msg, 'error');
		});
	})
</script>
@endpush
