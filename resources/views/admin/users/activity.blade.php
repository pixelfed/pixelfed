@extends('admin.partial.template-full')

@section('section')
<div class="title d-flex justify-content-between align-items-center">
	<span><a href="/i/admin/users/show/{{$user->id}}" class="btn btn-outline-secondary btn-sm font-weight-bold">Back</a></span>
	<span class="text-center">
		<h3 class="font-weight-bold mb-0">&commat;{{$profile->username}}</h3>
		<p class="mb-0 small text-muted text-uppercase font-weight-bold">
			<span>{{$profile->statuses()->count()}} Posts</span>
			<span class="px-1">|</span>
			<span>{{$profile->followers()->count()}} Followers</span>
			<span class="px-1">|</span>
			<span>{{$profile->following()->count()}} Following</span>
		</p>
	</span>
	<span>
		<div class="dropdown">
			<button class="btn btn-outline-secondary btn-sm font-weight-bold dropdown-toggle" type="button" id="userActions" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-bars"></i></button>
			<div class="dropdown-menu dropdown-menu-right" aria-labelledby="userActions">
				<a class="dropdown-item" href="/i/admin/users/show/{{$user->id}}">
					<span class="font-weight-bold">Overview</span>
				</a>
				<a class="dropdown-item" href="/i/admin/users/message/{{$user->id}}">
					<span class="font-weight-bold">Send Message</span>
				</a>
				<a class="dropdown-item" href="{{$profile->url()}}">
					<span class="font-weight-bold">View Profile</span>
				</a>
				<div class="dropdown-divider"></div>
				<a class="dropdown-item" href="/i/admin/users/edit/{{$user->id}}">
					<span class="font-weight-bold">Edit</span>
				</a>
				<a class="dropdown-item" href="/i/admin/users/modtools/{{$user->id}}">
					<span class="font-weight-bold">Mod Tools</span>
				</a>
				<a class="dropdown-item" href="/i/admin/users/modlogs/{{$user->id}}">
					<span class="font-weight-bold">Mod Logs</span>
				</a>
				<div class="dropdown-divider"></div>
				<a class="dropdown-item" href="/i/admin/users/delete/{{$user->id}}">
					<span class="text-danger font-weight-bold">Delete Account</span>
				</a>
			</div>
		</div>
	</span>
</div>
<hr>

<div class="row mb-3">
	<div class="col-12 col-md-8 offset-md-2">
		<p class="title h4 font-weight-bold mt-2 py-2">Recent Activity</p>
		<hr>
		<div class="row">
			<div class="col-12">
				@if($logs->count() > 0)
				<div class="list-group">
					@foreach($logs as $log)
					<div class="list-group-item d-flex justify-content-between align-items-center">
						<div>
							<p class="small text-muted font-weight-bold mb-0">{{$log->created_at->diffForHumans()}}</p>
							<p class="lead mb-0">{{$log->message}}</p>
							<p class="small text-muted font-weight-bold mb-0">
								IP: {{$log->ip_address}}
							</p>
						</div>
						<div>
							<i class="fas fa-chevron-right fa-lg text-lighter"></i>
						</div>
					</div>
					@endforeach
				</div>
				<div class="d-flex justify-content-center mt-3">
					{{$logs->links()}}
				</div>
				@else
				<div class="card card-body border shadow-none text-center">
					No Activity found
				</div>
				@endif
			</div>
		</div>
	</div>
</div>
@endsection