@extends('layouts.app')

@section('content')
<div class="container">	

	<div class="profile-header row my-5">	
		<div class="col-12 col-md-2">	
			<div class="profile-avatar">
				<div class="bg-pixelfed mb-3 d-flex align-items-center justify-content-center display-4 font-weight-bold text-white" style="width: 132px; height: 132px; border-radius: 100%"><i class="fas fa-map-pin"></i></div>
			</div>	
		</div>	
		<div class="col-12 col-md-9 d-flex align-items-center">	
			<div class="profile-details">	
				<div class="username-bar pb-2 d-flex align-items-center">
					<div class="ml-4">
						<p class="h3 font-weight-lighter">{{$place->name}}, {{$place->country}}</p>
						<p class="small text-muted">({{$place->lat}}, {{$place->long}})</p>	
					</div>	
				</div>	
			</div>	
		</div>	
	</div>	
	<div class="tag-timeline">	
		<div class="row">
			@if($posts->count() > 0)	
			@foreach($posts as $status)	
			<div class="col-4 p-0 p-sm-2 p-md-3">	
				<a class="card info-overlay card-md-border-0" href="{{$status->url()}}">	
					<div class="square {{$status->firstMedia()->filter_class}}">	
						<div class="square-content" style="background-image: url('{{$status->thumb()}}')"></div>	
					</div>	
				</a>	
			</div>	
			@endforeach	
			@else
			<div class="col-12 bg-white p-5 border">
				<p class="lead text-center text-dark mb-0">No results for this location</p>
			</div>
			@endif
		</div>	
	</div>	
</div>
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/compose.js') }}"></script>
<script type="text/javascript">App.boot();</script>
@endpush