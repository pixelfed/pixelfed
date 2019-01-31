@extends('admin.partial.template-full')

@section('section')
<div class="title">
	<h3 class="font-weight-bold d-inline-block">Media</h3>
	<p class="font-weight-lighter mb-0">ID: {{$media->id}}</p>
</div>
<hr>
<div class="row">
	<div class="col-12 col-md-8 offset-md-2">
		<div class="card">
			<img class="card-img-top" src="{{$media->thumb()}}">
			<ul class="list-group list-group-flush">
				<li class="list-group-item d-flex justify-content-between">
					<div>
						<p class="mb-0 small">status id: <a href="{{$media->status->url()}}" class="font-weight-bold">{{$media->status_id}}</a></p>
						<p class="mb-0 small">username: <a href="{{$media->profile->url()}}" class="font-weight-bold">{{$media->profile->username}}</a></p>
						<p class="mb-0 small">size: <span class="filesize font-weight-bold" data-size="{{$media->size}}">0</span></p>
					</div>
					<div>
						<p class="mb-0 small">mime: <span class="font-weight-bold">{{$media->mime}}</span></p>
						<p class="mb-0 small">content warning:  <i class="fas {{$media->is_nsfw  ? 'fa-check text-danger':'fa-times text-success'}}"></i></p>
						<p class="mb-0 small">
							remote media: <i class="fas {{$media->remote_media ? 'fa-check text-danger':'fa-times text-success'}}"></i></p>
					</div>
				</li>
			</ul>
		</div>
	</div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
	$(document).ready(function() {
		$('.filesize').each(function(k,v) {
			$(this).text(filesize(v.getAttribute('data-size')))
		});
	});
</script>
@endpush