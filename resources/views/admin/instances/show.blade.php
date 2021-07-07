@extends('admin.partial.template-full')

@section('section')
<div class="title">
	<div class="d-flex justify-content-between">
		<div>
			<h3 class="font-weight-bold mb-0">Instance Overview</h3>
			<p class="font-weight-lighter mb-0">domain: {{$instance->domain}}</p>
		</div>
		<div>
			<a class="btn btn-outline-primary btn-sm py-1" href="{{route('admin.instances')}}">Back</a>
		</div>
	</div>
</div>
<hr>
<div class="d-flex justify-content-between">
	<div>
		<p class="font-weight-lighter mb-0">unlisted: {{$instance->unlisted ? 'true' : 'false'}}</p>
	</div>
	<div>
		<p class="font-weight-lighter mb-0">CW media: {{$instance->auto_cw ? 'true' : 'false'}}</p>
	</div>
	<div>
		<p class="font-weight-lighter mb-0">banned: {{$instance->banned ? 'true' : 'false'}}</p>
	</div>
</div>
<hr>
<div class="row">
	<div class="col-12 col-md-6">
		<div class="card mb-3">
			<div class="card-body text-center">
				<p class="mb-0 font-weight-lighter display-4">
					{{$instance->profiles->count()}}
				</p>
				<p class="mb-0 text-muted">Profiles</p>
			</div>
		</div>
		<div class="card mb-3">
			<div class="card-body text-center">
				<p class="mb-0 font-weight-lighter display-4">
					{{$instance->reports->count()}}
				</p>
				<p class="mb-0 text-muted">Reports</p>
			</div>
		</div>
	</div>
	<div class="col-12 col-md-6">
		<div class="card mb-3">
			<div class="card-body text-center">
				<p class="mb-0 font-weight-lighter display-4">
					{{$instance->statuses->count()}}
				</p>
				<p class="mb-0 text-muted">Statuses</p>
			</div>
		</div>
		<div class="card mb-3">
			<div class="card-body text-center">
				<p class="mb-0 font-weight-lighter display-4 filesize" data-size="{{$instance->media()->sum('size')}}">
					0
				</p>
				<p class="mb-0 text-muted">Storage Used</p>
			</div>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-md-6">
		<div class="card">
			<div class="card-header bg-light h4 font-weight-lighter">
				Profiles
				<span class="float-right">
					<a class="btn btn-outline-secondary btn-sm py-0" href="#">View All</a>
				</span>
			</div>
			<ul class="list-group list-group-flush">
				@foreach($instance->profiles()->latest()->take(5)->get() as $profile)
				<li class="list-group-item">
					<a class="btn btn-outline-primary btn-block btn-sm" href="{{$profile->url()}}">{{$profile->emailUrl()}}</a>
				</li>
				@endforeach
			</ul>
		</div>
	</div>
	<div class="col-md-6">
		<div class="card">
			<div class="card-header bg-light h4 font-weight-lighter">
				Statuses
				<span class="float-right">
					<a class="btn btn-outline-secondary btn-sm py-0" href="#">View All</a>
				</span>
			</div>
			<ul class="list-group list-group-flush">
				@foreach($instance->statuses()->latest()->take(5)->get() as $status)
				<li class="list-group-item">
					<a class="btn btn-outline-primary btn-block btn-sm" href="{{$status->url()}}">Status ID: {{$status->id}}</a>
				</li>
				@endforeach
			</ul>
		</div>
	</div>
</div>
@endsection

@push('scripts')
<script type="text/javascript" src="{{mix('js/components.js')}}"></script>
<script type="text/javascript">
	$(document).ready(function() {
		$('.filesize').each(function(k,v) {
			$(this).text(filesize(v.getAttribute('data-size')))
		});
	});
</script>
@endpush
