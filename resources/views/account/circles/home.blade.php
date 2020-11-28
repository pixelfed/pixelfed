@extends('layouts.app')

@section('content')

<div class="container">
	<div class="row">
		<div class="col-12 py-5 d-flex justify-content-between align-items-center">
			<p class="h1 mb-0"><i class="far fa-circle"></i> Circles</p>
			<p class="mb-0"><a class="btn btn-outline-primary font-weight-bold btn-sm py-1" href="{{route('account.circles.create')}}"><i class="far fa-circle"></i> New</a></p>
		</div>
		<div class="col-12">
			<div class="card">
					<ul class="list-group list-group-flush">
						@foreach($circles as $circle)
						<li class="list-group-item py-3">
							<div class="row d-flex align-items-center">
								<div class="col-md-4">
									<p class="h1 font-weight-lighter mb-0">{{$circle->name}}</p>
								</div>
								<div class="col-md-4 text-center">
									@foreach($circle->members()->orderByDesc('created_at')->take(8)->get() as $member)
										<a href="{{$member->url()}}"><img src="{{$member->avatarUrl()}}" class="box-shadow rounded-circle ml-n3" width="40" style="border:3px solid #fff;"></a>
									@endforeach
								</div>
								<div class="col-md-4 text-right">
									<span class="font-weight-lighter mb-0 text-muted pr-3">{{$circle->members->count()}} Members</span>
									<a class="btn btn-outline-primary btn-sm py-1 font-weight-bold" href="{{$circle->url()}}">View</a>
								</div>
							</div>
						</li>
						@endforeach
					</ul>
			</div>
			<div class="d-flex justify-content-center mt-4">
				{{$circles->links()}}
			</div>
		</div>
	</div>
</div>

@endsection
