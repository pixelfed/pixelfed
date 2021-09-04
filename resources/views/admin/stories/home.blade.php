@extends('admin.partial.template-full')

@section('section')
</div>
<div class="header bg-primary pb-3 mt-n4">
	<div class="container-fluid">
		<div class="header-body">
			<div class="row align-items-center py-4">
				<div class="col-lg-6 col-7">
					<p class="display-1 text-white d-inline-block mb-0">Stories</p>
				</div>
			</div>
			<div class="row">
				<div class="col-xl-3 col-md-6">
					<div class="card card-stats">
						<div class="card-body">
							<div class="row">
								<div class="col">
									<h5 class="card-title text-uppercase text-muted mb-0">Active Stories</h5>
									<span class="h2 font-weight-bold mb-0">{{$stats['active']['today']}}</span>
								</div>
								<div class="col-auto">
									<div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
										<i class="ni ni-image"></i>
									</div>
								</div>
							</div>
							<p class="mt-3 mb-0 text-sm">
								<span class="text-success mr-2"><i class="fa fa-arrow-up"></i> {{$stats['active']['month']}}</span>
								<span class="text-nowrap">in last 30 days</span>
							</p>
						</div>
					</div>
				</div>
				<div class="col-xl-3 col-md-6">
					<div class="card card-stats">
						<div class="card-body">
							<div class="row">
								<div class="col">
									<h5 class="card-title text-uppercase text-muted mb-0">Remote Stories</h5>
									<span class="h2 font-weight-bold mb-0">{{$stats['remote']['month']}}</span>
								</div>
								<div class="col-auto">
									<div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
										<i class="ni ni-circle-08"></i>
									</div>
								</div>
							</div>
							<p class="mt-3 mb-0 text-sm">
								<span class="text-success mr-2"><i class="fa fa-arrow-up"></i> {{$stats['remote']['month']}}</span>
								<span class="text-nowrap">in last 30 days</span>
							</p>
						</div>
					</div>
				</div>
				<div class="col-xl-2 col-md-6">
					<div class="mb-3">
						<h5 class="text-light text-uppercase mb-0">Total Stories</h5>
						<span class="text-white h2 font-weight-bold mb-0">{{$stats['total']}}</span>
					</div>
					<div class="">
						<h5 class="text-light text-uppercase mb-0">Stories Per User</h5>
						<span class="text-white h2 font-weight-bold mb-0">{{$stats['avg_spu']}}</span>
					</div>
				</div>
				<div class="col-xl-2 col-md-6">
					<div class="mb-3">
						<h5 class="text-light text-uppercase mb-0">Storage Used</h5>
						<span class="text-white h2 font-weight-bold mb-0 human-size">{{$stats['storage']['sum']}}</span>
					</div>
					<div class="">
						<h5 class="text-light text-uppercase mb-0">Average Media Size</h5>
						<span class="text-white h2 font-weight-bold mb-0 human-size">{{$stats['storage']['average']}}</span>
					</div>
				</div>
				<div class="col-xl-2 col-md-6">
					<div class="mb-3">
						<h5 class="text-light text-uppercase mb-0">Average Duration</h5>
						<span class="text-white h2 font-weight-bold mb-0">{{$stats['avg_duration']}}s</span>
					</div>
					<div class="">
						<h5 class="text-light text-uppercase mb-0">Average Type</h5>
						<span class="text-white h2 font-weight-bold mb-0">{{$stats['avg_type']}}</span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="container-fluid mt-4">
	<div class="table-responsive">
		<table class="table table-dark">
			<thead class="thead-dark">
				<tr>
					<th scope="col" class="">#</th>
					<th scope="col" class="">Username</th>
					<th scope="col" class="">Type</th>
					<th scope="col" class="">View Count</th>
					<th scope="col" class="">Created</th>
					<th scope="col" class="">Expires</th>
					<th scope="col"></th>
				</tr>
			</thead>
			<tbody>
				@foreach($stories as $story)
				<tr>
					<th scope="row">
						<a href="{{$story->url()}}" class="text-monospace">
							{{$story->id}}
						</a>
					</th>
					<td class="font-weight-bold">{{$story->profile->username}}</td>
					<td class="font-weight-bold">{{$story->type}}</td>
					<td class="font-weight-bold">{{$story->view_count ?? 0}}</td>
					<td class="font-weight-bold">{{$story->created_at->diffForHumans(null, true, true, true)}}</td>
					<td class="font-weight-bold">{{optional($story->expires_at)->diffForHumans(null, true, true, true)}}</td>
					<td class="text-right">
						<div class="dropdown">
							<a class="btn btn-sm btn-icon-only text-light" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<i class="fas fa-ellipsis-v"></i>
							</a>
							<div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">
								<a class="dropdown-item" href="{{$story->mediaUrl()}}">Preview</a>
								<a class="dropdown-item" href="#">Delete</a>
							</div>
						</div>
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	<div class="d-flex justify-content-center mt-5 small">
		{{$stories->links()}}
	</div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
	$(document).ready(function() {
		$('.human-size').each(function(d,a) {
			a.innerText = filesize(a.innerText, {round: 0});
		});
	});
</script>
@endpush
