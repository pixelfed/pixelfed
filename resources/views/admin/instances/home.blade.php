@extends('admin.partial.template')

@section('section')
<div class="title">
	<h3 class="font-weight-bold">Instances</h3>
</div>

<hr>
@if($instances->count() == 0)
<div class="alert alert-warning mb-3">
	<p class="lead font-weight-bold mb-0">Warning</p>
	<p class="font-weight-lighter mb-0">No instances were found.</p>
</div>
<p class="font-weight-lighter">Do you want to scan and populate instances from Profiles and Statuses?</p>
<p>
	<form method="post">
		@csrf
		<button type="submit" class="btn btn-primary py-1 font-weight-bold">Run Scan</button>
	</form>
</p>
@else
<ul class="list-group">
	@foreach($instances as $instance)
	<li class="list-group-item">
		<div class="d-flex justify-content-between">
			<div>
				<p class="h4 font-weight-normal mb-1">
					{{$instance->domain}}
				</p>
				<p class="mb-0">
					<a class="btn btn-primary btn-sm py-0 font-weight-normal" href="#">Overview</a>
					<a class="btn btn-secondary btn-sm py-0 font-weight-normal" href="#">Actions</a>
				</p>
			</div>
			<div>
				<div class="d-inline-block pr-4">
					<p class="h4 font-weight-light text-center">{{$instance->profiles()->count()}}</p>
					<p class="mb-0 small font-weight-normal text-muted">Profiles</p>
				</div>
				<div class="d-inline-block pr-4">
					<p class="h4 font-weight-light text-center">{{$instance->statuses()->count()}}</p>
					<p class="mb-0 small font-weight-normal text-muted">Statuses</p>
				</div>
				<div class="d-inline-block pr-4">
					<p class="h4 font-weight-light text-center text-muted">{{$instance->reported()->count()}}</p>
					<p class="mb-0 small font-weight-normal text-muted">Reports</p>
				</div>
				<div class="d-inline-block">
					<p class="h4 font-weight-light text-center text-muted filesize" data-size="{{$instance->media()->sum('size')}}">0</p>
					<p class="mb-0 small font-weight-normal text-muted">Storage Used</p>
				</div>
			</div>
		</div>
	</li>
	@endforeach
</ul>
<div class="d-flex justify-content-center mt-5 small">
	{{$instances->links()}}
</div>
@endif
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