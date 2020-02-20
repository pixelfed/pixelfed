@extends('admin.partial.template-full')

@section('section')
<div class="title d-flex justify-content-between align-items-center">
	<span><a href="{{route('admin.users')}}" class="btn btn-outline-secondary btn-sm font-weight-bold">Back</a></span>
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
				<a class="dropdown-item" href="/i/admin/users/activity/{{$user->id}}">
					<span class="font-weight-bold">Activity</span>
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
	</div>
	<hr>

	<div class="row mb-3">
		<div class="col-12 col-md-4">
			<div class="card shadow-none border">
				<div class="card-body text-center">
					<img src="{{$profile->avatarUrl()}}" class="box-shadow rounded-circle" width="128px" height="128px">
					<p class="mt-3 mb-0 lead">
						<span class="font-weight-bold">{{$profile->name}}</span>
					</p>
					@if($user->is_admin == true)
					<p class="mb-0">
						<span class="badge badge-danger badge-sm">ADMIN</span>
					</p>
					@endif
					<p class="mb-0 text-center text-muted">
						Joined {{$profile->created_at->diffForHumans()}}
					</p>
				</div>
				<table class="table mb-0">
					<tbody>
						<tr>
							<th scope="row" class="font-weight-bold text-muted text-uppercase pl-3 small" style="line-height: 2;">bookmarks</th>
							<td class="text-right font-weight-bold">{{$profile->bookmarks()->count()}}</td>
						</tr>
						<tr>
							<th scope="row" class="font-weight-bold text-muted text-uppercase pl-3 small" style="line-height: 2;">collections</th>
							<td class="text-right font-weight-bold">{{$profile->collections()->count()}}</td>
						</tr>
						<tr>
							<th scope="row" class="font-weight-bold text-muted text-uppercase pl-3 small" style="line-height: 2;">likes</th>
							<td class="text-right font-weight-bold">{{$profile->likes()->count()}}</td>
						</tr>
						<tr>
							<th scope="row" class="font-weight-bold text-muted text-uppercase pl-3 small" style="line-height: 2;">reports</th>
							<td class="text-right font-weight-bold">{{$profile->reports()->count()}}</td>
						</tr>
						<tr>
							<th scope="row" class="font-weight-bold text-muted text-uppercase pl-3 small" style="line-height: 2;">reported</th>
							<td class="text-right font-weight-bold">{{$profile->reported()->count()}}</td>
						</tr>
						<tr>
							<th scope="row" class="font-weight-bold text-muted text-uppercase pl-3 small" style="line-height: 2;">Active stories</th>
							<td class="text-right font-weight-bold">{{$profile->stories()->count()}}</td>
						</tr>
						<tr>
							<th scope="row" class="font-weight-bold text-muted text-uppercase pl-3 small" style="line-height: 2;">storage used</th>
							<td class="text-right font-weight-bold">{{PrettyNumber::size($profile->media()->sum('size'))}}<span class="text-muted"> / {{PrettyNumber::size(config('pixelfed.max_account_size') * 1000)}}</span></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<div class="col-12 col-md-8">
			<p class="title h4 font-weight-bold mt-2 py-2">Recent Posts</p>
			<hr>
			<div class="row">
				@foreach($profile->statuses()->whereHas('media')->latest()->take(9)->get() as $item)
				<div class="col-12 col-md-4 col-sm-6 px-0" style="margin-bottom: 1px;">
					<a href="{{$item->url()}}">
						<img src="{{$item->thumb(true)}}" width="200px" height="200px">
					</a>
				</div>
				@endforeach

				@if($profile->statuses()->whereHas('media')->count() == 0)
				<div class="col-12">
					<div class="card card-body border shadow-none bg-transparent">
						<p class="text-center mb-0 text-muted">No statuses found</p>
					</div>
				</div>
				@endif
			</div>
		</div>
	</div>
	@endsection