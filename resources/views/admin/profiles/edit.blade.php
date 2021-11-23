@extends('admin.partial.template-full')

@section('section')
  <div class="title d-flex justify-content-between align-items-center">
    <span><a href="{{route('admin.profiles')}}" class="btn btn-outline-secondary btn-sm font-weight-bold">Back</a></span>
    <h3 class="font-weight-bold">Edit Profile</h3>
    <span><a href="#" class="btn btn-outline-primary btn-sm font-weight-bold disabled">Enable Editing</a></span>
  </div>
  <hr>

  <div class="row mb-3">
    <div class="col-12 col-md-4">
      <div class="card">
        <div class="card-body text-center">
          <img src="{{$profile->avatarUrl()}}" class="box-shadow rounded-circle" width="128px" height="128px">
        </div>
        {{-- <div class="card-footer bg-white">
          <p class="font-weight-bold mb-0 small">Last updated: {{$profile->avatar->updated_at->diffForHumans()}}</p>
        </div> --}}
      </div>
    </div>
    <div class="col-12 col-md-8">
      <table class="table table-striped table-borderless table-sm">
        <tbody>
          @if($user)
          <tr>
            <th scope="row">user id</th>
            <td>{{$user->id}}</td>
          </tr>
          @endif
          <tr>
            <th scope="row">profile id</th>
            <td>{{$profile->id}}</td>
          </tr>
          <tr>
            <th scope="row">username</th>
            <td>
              {{$profile->username}}
              @if($user && $user->is_admin == true)
                <span class="badge badge-danger ml-3">Admin</span>
              @endif
            </td>
          </tr>
          <tr>
            <th scope="row">display name</th>
            <td>{{$profile->name}}</td>
          </tr>
          <tr>
            <th scope="row">joined</th>
            <td>{{$profile->created_at->format('M j Y')}}</td>
          </tr>
          @if($user)
          <tr>
            <th scope="row">email</th>
            <td>
                {{$user->email}}
              @if($user->email_verified_at)
              <span class="text-success font-weight-bold small pl-2">Verified</span>
              @else
              <span class="text-danger font-weight-bold small pl-2">Unverified</span>
              @endif
            </td>
          </tr>
          @endif
        </tbody>
      </table>
      {{-- <div class="py-3">
        <p class="font-weight-bold mb-0">
          {{$profile->username}}
        </p>
        <p class="h3 font-weight-bold">
          {{$profile->emailUrl()}}
        </p>
        <p class="font-weight-bold mb-0 text-muted">
          Member Since: {{$profile->created_at->format('M Y')}}
        </p>
      </div> --}}
    </div>
  </div>
  <div class="row mb-3">
  	<div class="col-12 col-md-4 mb-3">
  		<div class="card">
  			<div class="card-body text-center">
  				<p class="h4 mb-0 font-weight-bold">{{$profile->statusCount()}}</p>
  				<p class="text-muted font-weight-bold small mb-0">Posts</p>
  			</div>
  		</div>
  	</div>
    <div class="col-12 col-md-4 mb-3">
      <div class="card">
        <div class="card-body text-center">
          <p class="h4 mb-0 font-weight-bold">{{$profile->followingCount()}}</p>
          <p class="text-muted font-weight-bold small mb-0">Following</p>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4 mb-3">
      <div class="card">
        <div class="card-body text-center">
          <p class="h4 mb-0 font-weight-bold">{{$profile->followerCount()}}</p>
          <p class="text-muted font-weight-bold small mb-0">Followers</p>
        </div>
      </div>
    </div>
  	<div class="col-12 col-md-3 mb-3">
  		<div class="card">
  			<div class="card-body text-center">
  				<p class="h4 mb-0 font-weight-bold">{{$profile->bookmarks()->count()}}</p>
  				<p class="text-muted font-weight-bold small mb-0">Bookmarks</p>
  			</div>
  		</div>
  	</div>
    <div class="col-12 col-md-3 mb-3">
      <div class="card">
        <div class="card-body text-center">
          <p class="h4 mb-0 font-weight-bold">{{$profile->likes()->count()}}</p>
          <p class="text-muted font-weight-bold small mb-0">Likes</p>
        </div>
      </div>
    </div>
  	<div class="col-12 col-md-3 mb-3">
  		<div class="card">
  			<div class="card-body text-center">
  				<p class="h4 mb-0 font-weight-bold">{{$profile->reports()->count()}}</p>
  				<p class="text-muted font-weight-bold small mb-0">Reports Made</p>
  			</div>
  		</div>
  	</div>
  	<div class="col-12 col-md-3 mb-3">
  		<div class="card">
  			<div class="card-body text-center">
  				<p class="h4 mb-0 font-weight-bold">{{PrettyNumber::size($profile->media()->sum('size'))}}</p>
  				<p class="text-muted font-weight-bold small mb-0">Storage Used</p>
  			</div>
  		</div>
  	</div>
  </div>

  <hr>
  {{-- <div class="mx-3">
  	  <div class="sub-title h4 font-weight-bold mb-4">
  	  	Account Settings
  	  </div>
	  <form>
	  	<div class="form-group">
	  		<label class="font-weight-bold text-muted">Display Name</label>
	  		<input type="text" class="form-control" value="{{$user->name}}">
	  	</div>
	  	<div class="form-group">
	  		<label class="font-weight-bold text-muted">Username</label>
	  		<input type="text" class="form-control" value="{{$user->username}}">
	  	</div>
	  	<div class="form-group">
	  		<label class="font-weight-bold text-muted">Email address</label>
	  		<input type="email" class="form-control" value="{{$user->email}}" placeholder="Enter email">
	        <p class="help-text small text-muted font-weight-bold">
	          @if($user->email_verified_at)
	          <span class="text-success">Verified</span> for {{$user->email_verified_at->diffForHumans()}}
	          @else
	          <span class="text-danger">Unverified</span> email.
	          @endif
	        </p>
	  	</div>
	  </form>
  </div>
  <hr> --}}
  <div class="mx-3">
      <div class="sub-title h4 font-weight-bold mb-4">
        Account Actions
      </div>
      <div class="row">

        <div class="col-12 col-md-4">
          <form method="post" action="/i/admin/users/moderation/update" class="pb-3">
            @csrf
            <input type="hidden" name="profile_id" value="{{$profile->id}}">
            <input type="hidden" name="action" value="cw">
            <button class="btn btn-outline-primary py-0 font-weight-bold">Enforce CW</button>
            <p class="help-text text-muted font-weight-bold small">Adds a CW to every post made by this account.</p>
          </form>
        </div>
        <div class="col-12 col-md-4">
          <form method="post" action="/i/admin/users/moderation/update" class="pb-3">
            @csrf
            <input type="hidden" name="profile_id" value="{{$profile->id}}">
            <input type="hidden" name="action" value="unlisted" />
            <button class="btn btn-outline-primary py-0 font-weight-bold">Unlisted Posts</button>
            <p class="help-text text-muted font-weight-bold small">Removes account from public/network timelines.</p>
          </form>
        </div>
        <div class="col-12 col-md-4">
          <form method="post" action="/i/admin/users/moderation/update" class="pb-3">
            @csrf
            <input type="hidden" name="profile_id" value="{{$profile->id}}">
            <input type="hidden" name="action" value="no_autolink" />
            <button class="btn btn-outline-primary py-0 font-weight-bold">No Autolinking</button>
            <p class="help-text text-muted font-weight-bold small">Do not transform mentions, hashtags or urls into HTML.</p>
          </form>
        </div>
        <div class="col-12 col-md-4">
          <form method="post" action="/i/admin/users/moderation/update" class="pb-3">
            @csrf
            <input type="hidden" name="profile_id" value="{{$profile->id}}">
            <button class="btn btn-outline-primary py-0 font-weight-bold">Disable Account</button>
            <p class="help-text text-muted font-weight-bold small">Temporarily disable account until next time user log in.</p>
          </form>
        </div>

        <div class="col-12 col-md-4">
          <form method="post" action="/i/admin/users/moderation/update" class="pb-3">
            @csrf
            <input type="hidden" name="profile_id" value="{{$profile->id}}">
            <button class="btn btn-outline-primary py-0 font-weight-bold">Suspend Account</button>
            <p class="help-text text-muted font-weight-bold small">This prevents any new interactions, without deleting existing data.</p>
          </form>
        </div>

        <div class="col-12 col-md-4">
          <form method="post" action="/i/admin/users/moderation/update" class="pb-3">
            @csrf
            <input type="hidden" name="profile_id" value="{{$profile->id}}">
            <button class="btn btn-outline-danger py-0 font-weight-bold">Lock down Account</button>
            <p class="help-text text-muted font-weight-bold small">This disables the account and changes the password, forcing account to reset password via verified email.</p>
          </form>
        </div>

        <div class="col-12">
          <form method="post" action="/i/admin/users/moderation/update" class="pb-3">
            @csrf
            <input type="hidden" name="profile_id" value="{{$profile->id}}">
            <button class="btn btn-outline-danger font-weight-bold btn-block">Delete Account</button>
            <p class="help-text text-muted font-weight-bold small">Permanently delete this account.</p>
          </form>
        </div>
      </div>
  </div>
@endsection