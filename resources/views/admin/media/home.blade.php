@extends('admin.partial.template-full')

@section('section')
<div class="title">
	<h3 class="font-weight-bold d-inline-block">Media</h3>
	<span class="float-right">
		<a class="btn btn-{{request()->input('layout')!=='banned'?'primary':'light'}} btn-sm font-weight-bold" href="{{route('admin.media')}}">
			All
		</a>
		<a class="btn btn-{{request()->input('layout')=='banned'?'primary':'light'}} btn-sm mr-3 font-weight-bold" href="{{route('admin.media',['layout'=>'banned', 'page' => request()->input('page') ?? 1])}}">
			Banned
		</a>
		<div class="dropdown d-inline-block">
			<button class="btn btn-light btn-sm dropdown-toggle font-weight-bold" type="button" id="filterDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<i class="fas fa-filter"></i>
			</button>
			<div class="dropdown-menu dropdown-menu-right" aria-labelledby="filterDropdown" style="width: 300px;">
				<div class="dropdown-item">
					<form action="/i/admin/media/?page=1">
						<input type="hidden" name="layout" value=""></input>
						<input type="hidden" name="page" value="{{request()->input('page')}}"></input>
						<div class="input-group input-group-sm">
							<input class="form-control" name="search" placeholder="Filter by username, mime type" autocomplete="off"></input>
							<div class="input-group-append">
								<button class="btn btn-outline-primary" type="submit">Filter</button>
							</div>
						</div>
					</form>
				</div>
				{{-- <div class="dropdown-divider"></div>
				<p class="text-wrap p-1 p-md-3 text-center">
					<a class="badge badge-primary p-2 mb-2 btn-filter" href="#" data-filter="cw" data-filter-state="true" data-toggle="tooltip" title="Show Content Warning media">CW</a> 
					<a class="badge badge-primary p-2 mb-2 btn-filter" href="#" data-filter="remote" data-filter-state="true" data-toggle="tooltip" title="Show remote media">Remote Media</a> 
					<a class="badge badge-primary p-2 mb-2 btn-filter" href="#" data-filter="images" data-filter-state="true" data-toggle="tooltip" title="Show image media">Images</a> 
					<a class="badge badge-primary p-2 mb-2 btn-filter" href="#" data-filter="videos" data-filter-state="true" data-toggle="tooltip" title="Show video media">Videos</a> 
					<a class="badge badge-light p-2 mb-2 btn-filter" href="#" data-filter="stories" data-filter-state="false" data-toggle="tooltip" title="Show stories media">Stories</a> 
					<a class="badge badge-light p-2 mb-2 btn-filter" href="#" data-filter="banned" data-filter-state="false" data-toggle="tooltip" title="Show banned media">Banned</a> 
					<a class="badge badge-light p-2 mb-2 btn-filter" href="#" data-filter="reported" data-filter-state="false" data-toggle="tooltip" title="Show reported media">Reported</a> 
					<a class="badge badge-light p-2 mb-2 btn-filter" href="#" data-filter="unlisted" data-filter-state="false" data-toggle="tooltip" title="Show unlisted media">Unlisted</a> 
				</p> --}}
				{{-- <div class="dropdown-divider"></div>
				<a class="dropdown-item font-weight-light" href="?filter=local&layout={{request()->input('layout')}}">Local Media Only</a>
				<a class="dropdown-item font-weight-light" href="?filter=remote&layout={{request()->input('layout')}}">Remote Media Only</a>
				<div class="dropdown-divider"></div>
				<a class="dropdown-item font-weight-light" href="?layout={{request()->input('layout')}}">Show all</a> --}}
			</div>
		</div>
	</span>
</div>

<hr>

@if(request()->filled('search'))
<p class="h4 pb-3">Showing results for: <i>{{request()->input('search')}}</i></p>
@endif

@if(request()->input('layout') == 'banned')
<p class="text-right">
	<a class="btn btn-primary py-0 px-5" href="/i/admin/media/?layout=addbanned">Add Banned Media</a>
</p>
<ul class="list-group">
	@foreach($media as $b)
	<li class="list-group-item">
		<div class="d-flex justify-content-between align-items-center">
			<div class="d-flex align-items-center">
				<span class="mr-4 text-monospace small">
					{{$b->id}}
				</span>
				<span class="d-inline-block">
					<p class="mb-0 small text-monospace">{{$b->sha256}}</p>
					<p class="mb-0 font-weight-bold">{{$b->name ?? 'Untitled'}}</p>
					<p class="mb-0 small">{{$b->description ?? 'No description'}}</p>
				</span>
			</div>
			<div class="small font-weight-bold">
				{{$b->created_at->diffForHumans()}}
			</div>
			<div class="">
				<form action="/i/admin/media/block/delete" method="post">
					@csrf
					<input type="hidden" name="id" value="{{$b->id}}">
					<button type="submit" class="btn btn-outline-danger">
						<i class="fas fa-trash-alt"></i>
					</button>
				</form>
			</div>
		</div>
	</li>
	@endforeach
</ul>

@elseif(request()->input('layout') == 'addbanned')
<div class="row">
	<div class="col-12 col-md-6 offset-md-3">
		<div class="card shadow-none border">
			<div class="card-header font-weight-bold">Add Banned Media</div>
			<div class="card-body">
				<form method="post" action="/i/admin/media/block/add">
					@csrf
					<div class="form-group">
						<label for="input3" class="text-muted font-weight-bold">SHA256 Hash</label>
						<input type="text" class="form-control" id="input3" aria-describedby="input3Help" name="hash">
						<small id="input3Help" class="form-text text-muted">Required</small>
					</div>
					<hr>
					<div class="form-group">
						<label for="input1" class="text-muted font-weight-bold">Name</label>
						<input type="text" class="form-control" id="input1" aria-describedby="input1Help" name="name">
						<small id="input1Help" class="form-text text-muted">Optional</small>
					</div>
					<div class="form-group">
						<label for="input2" class="text-muted font-weight-bold">Description</label>
						<textarea class="form-control" id="input2" aria-describedby="input2Help" rows="3" name="description"></textarea>
						<small id="input2Help" class="form-text text-muted">Optional</small>
					</div>
					<hr>
					<button type="submit" class="btn btn-primary btn-block font-weight-bold">Ban</button>
				</form>
			</div>
		</div>
		
	</div>
</div>

@else

<ul class="list-group">
	@foreach($media as $status)
	<li class="list-group-item">
		<div class="d-flex justify-content-between align-items-center">
			<div>
				<a class="font-weight-lighter small mr-3 text-monospace" href="/i/admin/media/show/{{$status->id}}">{{$status->id}}</a>
				<a href="{{$status->url()}}">
					<img class="" src="{{$status->thumb()}}" width="60px" height="60px">
				</a>
			</div>
			<div>
				<p class="mb-0 small">status id: <a href="/p/{{\App\Services\HashidService::encode($status->status_id)}}" class="font-weight-bold text-monospace">{{$status->status_id}}</a></p>
				<p class="mb-0 small">profile id: <a href="/i/admin/profiles/edit/{{$status->profile_id}}" class="font-weight-bold text-monospace">{{$status->profile_id}}</a></p>
			</div>
			<div>
				<p class="mb-0 small">size: <span class="filesize font-weight-bold" data-size="{{$status->size}}">0</span></p>
				<p class="mb-0 small">mime: <span class="font-weight-bold">{{$status->mime}}</span></p>
			</div>
			<div>
				<p class="mb-0 small">content warning:  <i class="fas {{$status->is_nsfw  ? 'fa-check text-danger':'fa-times text-dark'}}"></i></p>
				<p class="mb-0 small">
					remote media: <i class="fas {{$status->remote_media ? 'fa-check text-danger':'fa-times text-dark'}}"></i></p>
			</div>
		</div>
	</li>
	@endforeach
</ul>
<hr>
<div class="d-flex justify-content-center">
	{{$media->appends(['layout'=>request()->layout])->links()}}
</div>
@endif
@endsection

@push('scripts')
<script type="text/javascript">
	$(document).ready(function() {
		$('.filesize').each(function(k,v) {
			$(this).text(filesize(v.getAttribute('data-size')))
		});

		window.filters = {
			default() {
				return ['cw', 'remote', 'images', 'videos']
			},
			active() {
				return $('.btn-filter[data-filter-state="true"]');
			},
			whitelist() {
				return [
					'cw',
					'remote',
					'images',
					'videos',
					'stories',
					'banned',
					'reported',
					'unlisted',
				];
			},
			allowed(filter) {
				return _.indexOf(filters.whitelist(), filter) != -1;
			},
			buildQueryFragment(){
				window.filters.active().each(function(k,v) {
				})
			}
		}
		
		$('.badge.btn-filter').on('click', function(e) {
			e.preventDefault();
			let el = $(this);
			let filter = el.data('filter');
			let state = el.data('filter-state');
			if(state == false) {
				el.removeClass('badge-light')
				el.addClass('badge-primary')
				el.attr('data-filter-state', 'false')
			} else {
				el.removeClass('badge-primary')		
				el.addClass('badge-light')
				el.attr('data-filter-state', 'true')
			}
		});
	});
</script>
@endpush