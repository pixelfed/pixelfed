@extends('admin.partial.template-full')

@section('section')
<div class="title mb-3">
	<h3 class="font-weight-bold d-inline-block">Appeals</h3>
	<span class="float-right">
	</span>
</div>
<div class="row">
	<div class="col-12 col-md-3 mb-3">
		<div class="card border bg-primary text-white rounded-pill shadow">
			<div class="card-body pl-4 ml-3">
				<p class="h1 font-weight-bold mb-1" style="font-weight: 700">{{App\AccountInterstitial::whereNull('appeal_handled_at')->whereNotNull('appeal_requested_at')->count()}}</p>
				<p class="lead mb-0 font-weight-lighter">active appeals</p>				
			</div>
		</div>

		<div class="mt-3 card border bg-warning text-dark rounded-pill shadow">
			<div class="card-body pl-4 ml-3">
				<p class="h1 font-weight-bold mb-1" style="font-weight: 700">{{App\AccountInterstitial::whereNotNull('appeal_handled_at')->whereNotNull('appeal_requested_at')->count()}}</p>
				<p class="lead mb-0 font-weight-lighter">closed appeals</p>				
			</div>
		</div>
	</div>
	<div class="col-12 col-md-8 offset-md-1">
		<ul class="list-group">
			@if($appeals->count() == 0)
			<li class="list-group-item text-center py-5">
				<p class="mb-0 py-5 font-weight-bold">No appeals found!</p>
			</li>
			@endif
			@foreach($appeals as $appeal)
			<a class="list-group-item text-decoration-none text-dark" href="/i/admin/reports/appeal/{{$appeal->id}}">
				<div class="d-flex justify-content-between align-items-center">
					<div class="d-flex align-items-center">
						<img src="{{$appeal->has_media ? $appeal->status->thumb(true) : '/storage/no-preview.png'}}" width="64" height="64" class="rounded border">
						<div class="ml-2">
							<span class="d-inline-block text-truncate">
								<p class="mb-0 small font-weight-bold text-primary">{{$appeal->type}}</p>
								@if($appeal->item_type)
								<p class="mb-0 font-weight-bold">{{starts_with($appeal->item_type, 'App\\') ? explode('\\',$appeal->item_type)[1] : $appeal->item_type}}</p>
								@endif
							</span>
						</div>
					</div>
					<div class="d-block">
						<p class="mb-0 font-weight-bold">&commat;{{$appeal->user->username}}</p>
						<p class="mb-0 small text-muted font-weight-bold">{{$appeal->created_at->diffForHumans(null, null, true)}}</p>
					</div>
					<div class="d-inline-block">
						<p class="mb-0 small">
							<i class="fas fa-chevron-right fa-2x text-lighter"></i>
						</p>
					</div>
				</div>
			</a>
			@endforeach
		</ul>
		<p>{!!$appeals->render()!!}</p>
	</div>
</div>

@endsection