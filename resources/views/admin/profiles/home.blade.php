@extends('admin.partial.template-full')

@section('section')
<div class="title">
	<h3 class="font-weight-bold d-inline-block">Profiles</h3>
	<span class="float-right">
		<a class="btn btn-{{request()->input('layout')!=='list'?'primary':'light'}} btn-sm" href="{{route('admin.profiles')}}">
			<i class="fas fa-th"></i>
		</a>
		<a class="btn btn-{{request()->input('layout')=='list'?'primary':'light'}} btn-sm mr-3" href="{{route('admin.profiles',['layout'=>'list', 'page' => request()->input('page') ?? 1])}}">
			<i class="fas fa-list"></i>
		</a>
		<div class="dropdown d-inline-block">
			<button class="btn btn-light btn-sm dropdown-toggle font-weight-bold" type="button" id="filterDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<i class="fas fa-filter"></i>
			</button>
			<div class="dropdown-menu dropdown-menu-right" aria-labelledby="filterDropdown" style="width: 300px;">
				<div class="dropdown-item">
					<form>
						<input type="hidden" name="layout" value="{{request()->input('layout')}}"></input>
						<input type="hidden" name="page" value="{{request()->input('page')}}"></input>
						<div class="input-group input-group-sm">
							<input class="form-control" name="search" placeholder="Filter by username, mime type" autocomplete="off"></input>
							<div class="input-group-append">
								<button class="btn btn-outline-primary" type="submit">Filter</button>
							</div>
						</div>
					</form>
				</div>
				<div class="dropdown-divider"></div>
				<p class="text-wrap p-1 p-md-3 text-center">
					<a class="badge badge-primary p-1 btn-filter" href="#" data-filter="cw" data-filter-state="true" data-toggle="tooltip" title="Show Content Warning media">CW</a> 
					<a class="badge badge-primary p-1 btn-filter" href="#" data-filter="remote" data-filter-state="true" data-toggle="tooltip" title="Show remote media">Remote Media</a> 
					<a class="badge badge-primary p-1 btn-filter" href="#" data-filter="images" data-filter-state="true" data-toggle="tooltip" title="Show image media">Images</a> 
					<a class="badge badge-primary p-1 btn-filter" href="#" data-filter="videos" data-filter-state="true" data-toggle="tooltip" title="Show video media">Videos</a> 
					<a class="badge badge-light p-1 btn-filter" href="#" data-filter="stories" data-filter-state="false" data-toggle="tooltip" title="Show stories media">Stories</a> 
					<a class="badge badge-light p-1 btn-filter" href="#" data-filter="banned" data-filter-state="false" data-toggle="tooltip" title="Show banned media">Banned</a> 
					<a class="badge badge-light p-1 btn-filter" href="#" data-filter="reported" data-filter-state="false" data-toggle="tooltip" title="Show reported media">Reported</a> 
					<a class="badge badge-light p-1 btn-filter" href="#" data-filter="unlisted" data-filter-state="false" data-toggle="tooltip" title="Show unlisted media">Unlisted</a> 
				</p>
			</div>
		</div>
	</span>
</div>
<hr>
@if(request()->input('layout') == 'list')
<div class="table-responsive">
	<table class="table">
		<thead class="bg-light">
			<tr class="text-center">
				<th scope="col" class="border-0" width="10%">
					<span>ID</span> 
				</th>
				<th scope="col" class="border-0" width="30%">
					<span>Username</span>
				</th>
				<th scope="col" class="border-0" width="15%">
					<span>Statuses</span>
				</th>
				<th scope="col" class="border-0" width="15%">
					<span>Storage</span>
				</th>
				<th scope="col" class="border-0" width="30%">
					<span>Actions</span>
				</th>
			</tr>
		</thead>
		@foreach($profiles as $profile)
		<tr class="font-weight-bold text-center user-row">
			<th scope="row">
				{{$profile->id}}
			</th>
		</tr>
		@endforeach
	</tbody>
</table>
</div>
<div class="d-flex justify-content-center mt-5 small">
	{{$profiles->links()}}
</div>
@else
<div class="row">
	@foreach($profiles as $profile)
	<div class="col-12 col-md-4 mb-4">
		<div class="card">
			<div class="card-header bg-white text-center" style="min-height: 80px">
				<img class="box-shadow rounded-circle mb-3" src="{{$profile->avatarUrl()}}" width="64px">
				<p class="font-weight-bold mb-0 text-truncate">{{$profile->username}}</p>
			</div>
			<ul class="list-group list-group-flush small">
				<li class="list-group-item d-flex justify-content-between text-center">
					<div>
						<p class="font-weight-bold mb-0 h5">{{$profile->statuses()->count()}}</p>
						<span class="font-weight-bold text-muted" data-toggle="tooltip" title="Total Statuses"><i class="fas fa-sm fa-camera-retro"></i></span>
					</div>
					<div>
						<p class="font-weight-bold mb-0 h5 filesize" data-size="{{$profile->media()->sum('size')}}">0</p>
						<span class="font-weight-bold text-muted" data-toggle="tooltip" title="Storage space used"><i class="fas fa-sm fa-cloud-upload-alt"></i></span>
					</div>
					<div>
						<p class="font-weight-bold mb-0 h5">{{$profile->followers()->count()}}</p>
						<span class="font-weight-bold text-muted" data-toggle="tooltip" title="Total Followers"><i class="fas fa-sm fa-user-plus"></i></span>	
					</div>
				</li>
				<li class="list-group-item text-center">
					<a class="btn btn-outline-primary btn-sm py-0" href="{{$profile->url()}}">View</a>
					<a class="btn btn-outline-secondary btn-sm py-0" href="#">Actions</a>
				</li>
			</ul>
		</div>
	</div>
	@endforeach
</div>
<div class="d-flex justify-content-center mt-5 small">
	{{$profiles->links()}}
</div>
@endif
@endsection

@push('styles')
<style type="text/css">

.user-row .action-row {
	display: none;
}

.user-row:hover {
	background-color: #eff8ff;
}
.user-row:hover .action-row {
	display: block;
}
.user-row:hover .last-active {
	display: none;
}
</style>
@endpush
@push('scripts')
<script type="text/javascript">
	$('.filesize').each(function(k,v) {
		$(this).text(filesize(v.getAttribute('data-size'), {unix:true, round:0}))
	});
</script>
@endpush
