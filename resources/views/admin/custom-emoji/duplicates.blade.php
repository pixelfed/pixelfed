@extends('admin.partial.template-full')

@section('section')
</div>
<div class="header bg-primary pb-3 mt-n4">
	<div class="container-fluid">
		<div class="header-body">
			<div class="row align-items-center py-4">
				<div class="col-lg-6 col-7">
					<p class="display-1 text-white d-inline-block mb-1">Custom Emoji</p>
					<p class="h1 text-white font-weight-light d-inline-block mb-0">Showing duplicates of {{$emoji->shortcode}}</p>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="container mt-5">
	<div class="row justify-content-center">
		<div class="col-12 col-md-6">
			<div class="alert alert-warning py-2 mb-4">
				<p class="mb-0">
					<i class="far fa-exclamation-triangle mr-2"></i> Duplicate emoji shortcodes can lead to unpredictible results
				</p>
				<p class="mb-0 small">If you change the primary/in-use emoji, you will need to clear the cache by running the <strong>php artisan cache:clear</strong> command for the changes to take effect immediately.</p>
			</div>

			<p class="font-weight-bold">In Use</p>
			<div class="list-group">
				<div class="list-group-item">
					<div class="media align-items-center">
						<img src="{{url('storage/' . $emoji->media_path)}}" width="40" height="40" class="mr-3">

						<div class="media-body">
							<p class="font-weight-bold mb-0">{{ $emoji->shortcode }}</p>
							<p class="text-muted small mb-0">{{ $emoji->domain }}</p>
						</div>

						<div class="ml-3 badge badge-info">Added {{$emoji->created_at->diffForHumans(null, true, true)}}</div>

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

					</div>
				</div>
			</div>
			<hr>
			<p class="font-weight-bold">Not used (due to conflicting shortcode)</p>
			<div class="list-group">
				@foreach($emojis as $emoji)
				<div class="list-group-item">
					<div class="media align-items-center">
						<img src="{{url('storage/' . $emoji->media_path)}}" width="40" height="40" class="mr-3">

						<div class="media-body">
							<p class="font-weight-bold mb-0">{{ $emoji->shortcode }}</p>
							<p class="text-muted small mb-0">{{ $emoji->domain }}</p>
						</div>

						<div class="ml-3 badge badge-info">Added {{$emoji->created_at->diffForHumans(null, true, true)}}</div>

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
