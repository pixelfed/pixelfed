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

<div class="col-12 col-md-8 offset-md-2">
	<p class="title h4 font-weight-bold mt-2 py-2">Edit</p>
	<hr>
	<div class="row">
		<div class="col-12">
			<form method="post">
				@csrf
				<div class="form-group">
					<label class="font-weight-bold text-muted">Display Name</label>
					<input type="text" class="form-control" name="name" value="{{$user->name}}">
				</div>
				<div class="form-group">
					<label class="font-weight-bold text-muted">Username</label>
					<input type="text" class="form-control" name="username" value="{{$user->username}}">
				</div>
				<div class="form-group">
					<label class="font-weight-bold text-muted">Email address</label>
					<input type="email" class="form-control" name="email" value="{{$user->email}}" placeholder="Enter email">
					<p class="help-text small text-muted font-weight-bold">
						@if($user->email_verified_at)
						<span class="text-success">Verified</span> for {{$user->email_verified_at->diffForHumans()}}
						@else
						<span class="text-danger">Unverified</span> email.
						@endif
					</p>
				</div>
				<div class="form-group">
					<label class="font-weight-bold text-muted">Bio</label>
					<textarea class="form-control" rows="4" name="bio" placeholder="Empty bio">{{$profile->bio}}</textarea>
				</div>
				<div class="form-group">
					<label class="font-weight-bold text-muted">Website</label>
					<input type="text" class="form-control" name="website" value="{{$profile->website}}" placeholder="No website added">
				</div>
				<div class="form-group">
					<label class="font-weight-bold text-muted">Admin</label>
					<div class="custom-control custom-switch">
						<input type="checkbox" class="custom-control-input" id="customSwitch1" {{$user->is_admin ? 'checked="checked"' : ''}}>
						<label class="custom-control-label" for="customSwitch1"></label>
					</div>
					<p class="help-text small text-muted font-weight-bold">For security reasons, you cannot change admin status on this form. Use the CLI instead.</p>
				</div>
				<hr>
				<p class="float-right">
					<button type="submit" class="btn btn-primary font-weight-bold py-1">SAVE</button>
				</p>
			</form>
		</div>
	</div>
</div>

@endsection
