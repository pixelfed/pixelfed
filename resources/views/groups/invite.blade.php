@extends('layouts.app')

@section('content')
<div class="row align-items-center" style="display: flex;width: 100%;height: 100vh;overflow: hidden;">
	<div class="col-12 col-md-6 h-100 bg-primary d-flex align-items-center justify-content-center">
		<div class="d-flex flex-column align-items-center" style="max-width: 300px;">
			<p class="h3 text-center text-light font-weight-light">You were invited to join the <strong>{{ $group->name }}</strong> group.</p>
			<div class="card card-body mt-3">
				<div class="media align-items-center">
					<img src="{{$group->metadata['header']['url'] }}" width="64" height="64" class="rounded-circle mr-3">
					<div class="media-body">
						<p class="lead mb-0">{{ $group->name }}</p>
						<p class="small mb-0">34 members</p>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="col-12 col-md-6 h-100 d-flex align-items-center">
		<p></p>
	</div>
</div>
@endsection
