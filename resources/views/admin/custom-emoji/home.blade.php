@extends('admin.partial.template-full')

@section('section')
</div>
<div class="header bg-primary pb-3 mt-n4">
	<div class="container-fluid">
		<div class="header-body">
			<div class="row align-items-center py-4">
				<div class="col-lg-6 col-7">
					<p class="display-1 text-white d-inline-block mb-0">Custom Emoji</p>
				</div>
			</div>
			<div class="row">
				<div class="col-xl-2 col-md-6">
					<div class="mb-3">
						<h5 class="text-light text-uppercase mb-0">Total Emoji</h5>
						<span class="text-white h2 font-weight-bold mb-0 human-size">{{$stats['total']}}</span>
					</div>
				</div>
				<div class="col-xl-2 col-md-6">
					<div class="mb-3">
						<h5 class="text-light text-uppercase mb-0">Total Active</h5>
						<span class="text-white h2 font-weight-bold mb-0 human-size">{{$stats['active']}}</span>
					</div>
				</div>
				<div class="col-xl-2 col-md-6">
					<div class="mb-3">
						<h5 class="text-light text-uppercase mb-0">Remote Emoji</h5>
						<span class="text-white h2 font-weight-bold mb-0 human-size">{{$stats['remote']}}</span>
					</div>
				</div>
				<div class="col-xl-2 col-md-6">
					<div class="mb-3">
						<h5 class="text-light text-uppercase mb-0">Duplicate Emoji</h5>
						<span class="text-white h2 font-weight-bold mb-0 human-size">{{$stats['duplicate']}}</span>
					</div>
				</div>
				<div class="col-xl-4 col-md-6">
					<a
						class="btn btn-dark btn-lg px-3 mb-1"
						href="/i/admin/custom-emoji/new">
						<i class="far fa-plus mr-1"></i>
						Add Custom Emoji
					</a>
				</div>
			</div>
			<div class="row">
				<div class="col-12 mt-2">
					<p class="font-weight-light text-white small mb-0">
						Stats are cached for 12 hours and may not reflect the latest data.<br /> To refresh the cache and view the most recent data, <a href="/i/admin/custom-emoji/home?cc=1" class="font-weight-bold text-white">click here</a>.
					</p>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="container mt-5">

	<div class="row justify-content-center">
		<div class="col-12 col-md-6">
			<form method="get" class="mb-3" id="duplicate-form">
				<input type="hidden" name="sort" value="search">
				<input class="form-control rounded-pill" name="q" placeholder="Search by shortcode or domain name" value="{{request()->input('q')}}">
				@if($sort == 'search')
				<div class="custom-control custom-checkbox mt-1">
					<input type="checkbox" class="custom-control-input" id="showDuplicate" name="dups" value="1" onclick="document.getElementById('duplicate-form').submit()" {{ request()->has('dups') ? 'checked' : ''}}>
					<label class="custom-control-label" for="showDuplicate">Show duplicate results</label>
				</div>
				@endif
			</form>

			@if($sort != 'search')
			<ul class="nav nav-pills my-3 nav-fill">
				<li class="nav-item">
					<a class="nav-link {{$sort=='all'?'active':''}}" href="?sort=all">All</a>
				</li>

				<li class="nav-item">
					<a class="nav-link {{$sort=='local'?'active':''}}" href="?sort=local">Local</a>
				</li>

				<li class="nav-item">
					<a class="nav-link {{$sort=='remote'?'active':''}}" href="?sort=remote">Remote</a>
				</li>

				<li class="nav-item">
					<a class="nav-link {{$sort=='duplicates'?'active':''}}" href="?sort=duplicates">Duplicates</a>
				</li>

				<li class="nav-item">
					<a class="nav-link {{$sort=='disabled'?'active':''}}" href="?sort=disabled">Disabled</a>
				</li>
			</ul>
			@endif

			@if($sort == 'duplicates')
			<div class="alert alert-warning py-2 mt-4">
				<p class="mb-0">
					<i class="far fa-exclamation-triangle mr-2"></i> Duplicate emoji shortcodes can lead to unpredictible results
				</p>
			</div>
			@endif

			<div class="list-group">
				@foreach($emojis as $emoji)
				<div class="list-group-item">
					<div class="media align-items-center">
						<img src="{{url('storage/' . $emoji->media_path)}}" width="40" height="40" class="mr-3">

						<div class="media-body">
							<p class="font-weight-bold mb-0">{{ $emoji->shortcode }}</p>
							@if($emoji->domain != config('pixelfed.domain.app'))
							<p class="text-muted small mb-0">{{ $emoji->domain }}</p>
							@endif
						</div>

					@if($sort == 'duplicates')
						<a
							class="btn btn-primary rounded-pill btn-sm px-2 py-1 ml-3"
							href="/i/admin/custom-emoji/duplicates/{{$emoji->shortcode}}">
							View duplicates
						</a>
						{{-- <div class="ml-3 badge badge-info">Updated {{$emoji->updated_at->diffForHumans(null, true, true)}}</div> --}}
					@else
						<div class="ml-3 badge badge-info">Updated {{$emoji->updated_at->diffForHumans(null, true, true)}}</div>

						<form
							class="form-inline"
							action="/i/admin/custom-emoji/toggle-active/{{$emoji->id}}"
							method="post">
							@csrf
							<button
								type="submit"
								class="ml-3 btn btn-sm {{$emoji->disabled ? 'btn-danger' : 'btn-success'}}">
								{{$emoji->disabled ? 'Disabled' : 'Active' }}
							</button>
						</form>

						<button class="btn btn-danger px-2 py-1 ml-3 delete-emoji" data-id="{{$emoji->id}}">
							<i class="far fa-trash-alt"></i>
						</button>
					@endif

					</div>
				</div>
				@endforeach
			</div>

			<div class="d-flex justify-content-center mt-3">
				{{ $emojis->links() }}
			</div>
		</div>
	</div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
	$('.delete-emoji').click(function(i) {
		if(!window.confirm('Are you sure you want to delete this custom emoji?')) {
			return;
		}
		let id = i.currentTarget.getAttribute('data-id');
		axios.post('/i/admin/custom-emoji/delete/' + id)
		.then(res => {
			$(i.currentTarget).closest('.list-group-item').remove();
		})
	});
</script>
@endpush
