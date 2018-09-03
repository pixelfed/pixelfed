@extends('admin.partial.template')

@section('section')
  <div class="title d-flex justify-content-between">
    <h3 class="font-weight-bold">Edit User</h3>
    <span><a href="{{route('admin.users')}}" class="btn btn-outline-primary btn-sm font-weight-bold">Back</a></span>
  </div>
  <hr>

  <div class="row mb-3">
  	<div class="col-12 col-md-3">
  		<div class="card">
  			<div class="card-body text-center">
  				<p class="h4 mb-0 font-weight-bold">{{$profile->statusCount()}}</p>
  				<p class="text-muted font-weight-bold small mb-0">Posts</p>
  			</div>
  		</div>
  	</div>
  	<div class="col-12 col-md-3">
  		<div class="card">
  			<div class="card-body text-center">
  				<p class="h4 mb-0 font-weight-bold">{{$profile->likes()->count()}}</p>
  				<p class="text-muted font-weight-bold small mb-0">Likes</p>
  			</div>
  		</div>
  	</div>
  	<div class="col-12 col-md-3">
  		<div class="card">
  			<div class="card-body text-center">
  				<p class="h4 mb-0 font-weight-bold">{{$profile->reports()->count()}}</p>
  				<p class="text-muted font-weight-bold small mb-0">Reports</p>
  			</div>
  		</div>
  	</div>
  	<div class="col-12 col-md-3">
  		<div class="card">
  			<div class="card-body text-center">
  				<p class="h4 mb-0 font-weight-bold">{{PrettyNumber::size($profile->media()->sum('size'))}}</p>
  				<p class="text-muted font-weight-bold small mb-0">Storage Used</p>
  			</div>
  		</div>
  	</div>
  </div>

  <div class="row mb-2">
  	<div class="col-12 col-md-4">
  		<div class="card">
  			<div class="card-body text-center">
  				<img src="{{$profile->avatarUrl()}}" class="img-thumbnail rounded-circle" width="128px" height="128px">
  			</div>
  			<div class="card-footer bg-white">
  				<p class="font-weight-bold mb-0 small">Last updated: {{$profile->avatar->updated_at->diffForHumans()}}</p>
  			</div>
  		</div>
  	</div>
  	<div class="col-12 col-md-8">
  		<div class="card">
  			<div class="card-body p-5 d-flex justify-content-center align-items-center">
  				<div class="text-center py-3">
	  				<p class="font-weight-bold mb-0">
	  					{{$profile->username}}
	  				</p>
	  				<p class="h3 font-weight-bold">
	  					{{$profile->emailUrl()}}
	  				</p>
	  				<p class="font-weight-bold mb-0 text-muted">
	  					Member Since: {{$profile->created_at->format('M Y')}}
	  				</p>
  				</div>
  			</div>
  		</div>
  	</div>
  </div>
  <hr>
  <div class="mx-3">
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
@endsection