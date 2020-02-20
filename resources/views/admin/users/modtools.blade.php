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
				<a class="dropdown-item" href="/i/admin/users/edit/{{$user->id}}">
					<span class="font-weight-bold">Edit</span>
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
		<p class="title h4 font-weight-bold mt-2 py-2">Mod Tools</p>
		<hr>
		<div class="row">
			<div class="col-12 col-md-6">
				<form method="post" action="/i/admin/users/moderation/update" class="pb-3">
					@csrf
					<input type="hidden" name="profile_id" value="{{$profile->id}}">
					<button class="btn btn-outline-primary py-0 font-weight-bold">Enforce CW</button>
					<p class="help-text text-muted font-weight-bold small">Adds a CW to every post made by this account.</p>
				</form>
			</div>
			<div class="col-12 col-md-6">
				<form method="post" action="/i/admin/users/moderation/update" class="pb-3">
					@csrf
					<input type="hidden" name="profile_id" value="{{$profile->id}}">
					<button class="btn btn-outline-primary py-0 font-weight-bold">Unlisted Posts</button>
					<p class="help-text text-muted font-weight-bold small">Removes account from public/network timelines.</p>
				</form>
			</div>
			<div class="col-12 col-md-6">
				<form method="post" action="/i/admin/users/moderation/update" class="pb-3">
					@csrf
					<input type="hidden" name="profile_id" value="{{$profile->id}}">
					<button class="btn btn-outline-primary py-0 font-weight-bold">No Autolinking</button>
					<p class="help-text text-muted font-weight-bold small">Do not transform mentions, hashtags or urls into HTML.</p>
				</form>
			</div>
			<div class="col-12 col-md-6">
				<form method="post" action="/i/admin/users/moderation/update" class="pb-3">
					@csrf
					<input type="hidden" name="profile_id" value="{{$profile->id}}">
					<button class="btn btn-outline-primary py-0 font-weight-bold">Disable Account</button>
					<p class="help-text text-muted font-weight-bold small">Temporarily disable account until next time user log in.</p>
				</form>
			</div>

			<div class="col-12 col-md-6">
				<form method="post" action="/i/admin/users/moderation/update" class="pb-3">
					@csrf
					<input type="hidden" name="profile_id" value="{{$profile->id}}">
					<button class="btn btn-outline-primary py-0 font-weight-bold">Suspend Account</button>
					<p class="help-text text-muted font-weight-bold small">This prevents any new interactions, without deleting existing data.</p>
				</form>
			</div>

			<div class="col-12 col-md-6">
				<form method="post" action="/i/admin/users/moderation/update" class="pb-3">
					@csrf
					<input type="hidden" name="profile_id" value="{{$profile->id}}">
					<button class="btn btn-outline-danger py-0 font-weight-bold">Lock down Account</button>
					<p class="help-text text-muted font-weight-bold small">This disables the account and changes the password, forcing account to reset password via verified email.</p>
				</form>
			</div>
		</div>
	</div>
</div>
@endsection