@extends('admin.partial.template-full')

@section('section')
<div class="title">
	<h3 class="font-weight-bold d-inline-block">Media</h3>
	<span class="float-right">
		<a class="btn btn-{{request()->input('layout')!=='list'?'primary':'light'}} btn-sm" href="{{route('admin.media')}}">
			<i class="fas fa-th"></i>
		</a>
		<a class="btn btn-{{request()->input('layout')=='list'?'primary':'light'}} btn-sm mr-3" href="{{route('admin.media',['layout'=>'list', 'page' => request()->input('page') ?? 1])}}">
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
					<a class="badge badge-primary p-2 mb-2 btn-filter" href="#" data-filter="cw" data-filter-state="true" data-toggle="tooltip" title="Show Content Warning media">CW</a> 
					<a class="badge badge-primary p-2 mb-2 btn-filter" href="#" data-filter="remote" data-filter-state="true" data-toggle="tooltip" title="Show remote media">Remote Media</a> 
					<a class="badge badge-primary p-2 mb-2 btn-filter" href="#" data-filter="images" data-filter-state="true" data-toggle="tooltip" title="Show image media">Images</a> 
					<a class="badge badge-primary p-2 mb-2 btn-filter" href="#" data-filter="videos" data-filter-state="true" data-toggle="tooltip" title="Show video media">Videos</a> 
					<a class="badge badge-light p-2 mb-2 btn-filter" href="#" data-filter="stories" data-filter-state="false" data-toggle="tooltip" title="Show stories media">Stories</a> 
					<a class="badge badge-light p-2 mb-2 btn-filter" href="#" data-filter="banned" data-filter-state="false" data-toggle="tooltip" title="Show banned media">Banned</a> 
					<a class="badge badge-light p-2 mb-2 btn-filter" href="#" data-filter="reported" data-filter-state="false" data-toggle="tooltip" title="Show reported media">Reported</a> 
					<a class="badge badge-light p-2 mb-2 btn-filter" href="#" data-filter="unlisted" data-filter-state="false" data-toggle="tooltip" title="Show unlisted media">Unlisted</a> 
				</p>
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

@if(request()->input('layout') == 'list')
<ul class="list-group">
	@foreach($media as $status)
	<li class="list-group-item">
		<div class="d-flex justify-content-between align-items-center">
			<div>
				<a class="font-weight-lighter small mr-3" href="/i/admin/media/show/{{$status->id}}">{{$status->id}}</a>
				<a href="{{$status->url()}}">
					<img class="" src="{{$status->thumb()}}" width="60px" height="60px">
				</a>
			</div>
			<div>
				<p class="mb-0 small">status id: <a href="{{$status->status->url()}}" class="font-weight-bold">{{$status->status_id}}</a></p>
				<p class="mb-0 small">username: <a href="{{$status->profile->url()}}" class="font-weight-bold">{{$status->profile->username}}</a></p>
				<p class="mb-0 small">size: <span class="filesize font-weight-bold" data-size="{{$status->size}}">0</span></p>
			</div>
			<div>
				<p class="mb-0 small">mime: <span class="font-weight-bold">{{$status->mime}}</span></p>
				<p class="mb-0 small">content warning:  <i class="fas {{$status->is_nsfw  ? 'fa-check text-danger':'fa-times text-success'}}"></i></p>
				<p class="mb-0 small">
					remote media: <i class="fas {{$status->remote_media ? 'fa-check text-danger':'fa-times text-success'}}"></i></p>
			</div>
			<div>
				<a class="btn btn-outline-secondary btn-sm py-0" href="#">Actions</a>
			</div>
		</div>
	</li>
	@endforeach
</ul>
<hr>
<div class="d-flex justify-content-center">
	{{$media->links()}}
</div>
@else
<div class="profile-timeline mt-5 row">
	@foreach($media as $status)
	<div class="col-12 col-md-4 mb-4">
		<a class="card" href="{{$status->status->url()}}">
			<img class="card-img-top" src="{{$status->thumb()}}" width="150px" height="150px">
		</a>
	</div>
	@endforeach
</div>
<hr>
<div class="d-flex justify-content-center">
	{{$media->links()}}
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