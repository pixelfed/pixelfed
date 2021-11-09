@extends('admin.partial.template-full')

@section('section')
	<div class="title mb-3 d-flex justify-content-between align-items-center">
		<h3 class="font-weight-bold d-inline-block">Reports</h3>
		<div class="float-right">
			@if(request()->has('filter') && request()->filter == 'closed')
			<a class="mr-3 font-weight-light small text-muted" href="{{route('admin.reports')}}">
				View Open Reports
			</a>
			@else
			<a class="mr-3 font-weight-light small text-muted" href="{{route('admin.reports',['filter'=>'closed'])}}">
				View Closed Reports
			</a>
			@endif
		</div>
	</div>

	@if($ai || $spam || $mailVerifications)
	<div class="col-12 col-md-8 offset-md-2">
		<div class="mb-4">
			<a class="btn btn-outline-primary px-5 py-3 mr-3" href="/i/admin/reports/email-verifications">
				<p class="font-weight-bold h4 mb-0">{{$mailVerifications}}</p>
				Email Verify {{$mailVerifications == 1 ? 'Request' : 'Requests'}}
			</a>
			<a class="btn btn-outline-primary px-5 py-3 mr-3" href="/i/admin/reports/appeals">
				<p class="font-weight-bold h4 mb-0">{{$ai}}</p>
				Appeal {{$ai == 1 ? 'Request' : 'Requests'}}
			</a>
			<a class="btn btn-outline-primary px-5 py-3" href="/i/admin/reports/autospam">
				<p class="font-weight-bold h4 mb-0">{{$spam}}</p>
				Flagged {{$ai == 1 ? 'Post' : 'Posts'}}
			</a>
		</div>
	</div>
	@endif
	@if($reports->count())
	<div class="col-12 col-md-8 offset-md-2">
		<div class="card shadow-none border">
			<div class="list-group list-group-flush">
				@foreach($reports as $report)
				<div class="list-group-item p-1 {{$report->admin_seen ? 'bg-light' : 'bg-white'}}">
					<div class="p-0">
						<div class="media d-flex align-items-center">
							<a class="text-decoration-none" href="{{$report->url()}}">
								<img src="{{$report->status->media->count() ? $report->status->thumb(true) : '/storage/no-preview.png'}}" width="64" height="64" class="rounded border shadow mr-3" style="object-fit: cover">
							</a>
							<div class="media-body">
								<p class="mb-1 small"><span class="font-weight-bold text-uppercase text-danger">{{$report->type}}</span></p>
								@if($report->reporter && $report->status)
								<p class="mb-0"><a class="font-weight-bold text-dark" href="{{$report->reporter->url()}}">{{$report->reporter->username}}</a> reported this <a href="{{$report->status->url()}}" class="font-weight-bold text-dark">post</a></p>
								@else
								<p class="mb-0 lead">
									@if(!$report->reporter)
									<span class="font-weight-bold text-dark">Deleted user</span>
									@else
									<a class="font-weight-bold text-dark" href="{{$report->reporter->url()}}">{{$report->reporter->username}}</a> 
									@endif
									reported this 
									@if(!$report->status)
									<span class="font-weight-bold text-muted">deleted post</span>
									@else
									<a href="{{$report->status->url()}}" class="font-weight-bold text-dark">post</a> 
									@endif

								</p>

								@endif
							</div>
							<div class="float-right">
								@if($report->status)
								<a class="text-lighter p-2 text-decoration-none" href="{{$report->url()}}">
									View <i class="fas fa-chevron-right ml-2"></i>
								</a>
								@endif
							</div>
						</div>
					</div>
				</div>
				@endforeach
			</div>
		</div>
	</div>
	@else
	<div class="card shadow-none border">
		<div class="card-body">
			<p class="mb-0 p-5 text-center font-weight-bold lead">No reports found</p>
		</div>
	</div>
	@endif

	<div class="d-flex justify-content-center mt-5 small">
		{{$reports->appends(['layout'=>request()->layout, 'filter' => request()->filter])->links()}}
	</div>
@endsection
