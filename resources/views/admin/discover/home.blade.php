@extends('admin.partial.template-full')

@section('section')
<div class="title">
	<h3 class="font-weight-bold d-inline-block">Discover</h3>
	<span class="float-right">
		<a class="btn btn-outline-primary btn-sm py-1" href="{{route('admin.discover.create-category')}}">Create</a>
	</span>
</div>

<hr>
<ul class="list-group">
	@foreach($categories as $category)
	<li class="list-group-item">
		<div class="d-flex justify-content-between align-items-center">
			<div>
				<a class="font-weight-lighter small mr-3" href="/i/admin/media/show/{{$category->id}}">{{$category->id}}</a>
				<a href="{{$category->url()}}">
					<img class="" src="{{$category->thumb()}}" width="60" height="60">
				</a>
			</div>
			<div>
				<p class="lead mb-0">{{$category->slug}}</p>
			</div>
			<div>
				<div class="d-inline-block text-center px-3">
					<p class="h3 mb-0 font-weight-lighter">{{$category->hashtags()->count()}}</p>
					<p class="mb-0 small font-weight-light text-muted">Hashtags</p>
				</div>
				<div class="d-inline-block text-center px-3">
					<p class="h3 mb-0 font-weight-lighter">{{$category->posts()->count()}}</p>
					<p class="mb-0 small font-weight-light text-muted">Posts</p>
				</div>
			</div>
			<div>
				@if($category->active)
					<span class="badge badge-success mr-3">Active</span>
				@endif
				<a class="btn btn-outline-secondary btn-sm py-0 mr-3" href="{{$category->editUrl()}}">Edit</a>
				<a class="btn btn-outline-secondary btn-sm py-0" href="{{$category->url()}}">View</a>
			</div>
		</div>
	</li>
	@endforeach
</ul>
<hr>
<div class="d-flex justify-content-center">
	{{$categories->links()}}
</div>
@endsection