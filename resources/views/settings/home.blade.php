@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Account Settings</h3>
  </div>
  <hr>
  <form method="post">
    @csrf
    <div class="form-group row">
      <label for="name" class="col-sm-3 col-form-label font-weight-bold text-right">Name</label>
      <div class="col-sm-9">
        <input type="text" class="form-control" id="name" name="name" placeholder="Your Name" value="{{Auth::user()->profile->name}}">
      </div>
    </div>
    <div class="form-group row">
      <label for="name" class="col-sm-3 col-form-label font-weight-bold text-right">Username</label>
      <div class="col-sm-9">
        <input type="text" class="form-control" id="name" name="username" placeholder="Username" value="{{Auth::user()->profile->username}}" readonly>
      </div>
    </div>
    <div class="form-group row">
      <label class="col-sm-3 col-form-label font-weight-bold text-right">Bio</label>
      <div class="col-sm-9">
        <textarea class="form-control" name="bio" placeholder="Add a bio here" rows="2">{{Auth::user()->profile->bio}}</textarea>
      </div>
    </div>
    <div class="pt-5">
      <p class="font-weight-bold text-muted text-center">Private Information</p>
    </div>
    <div class="form-group row">
      <label for="email" class="col-sm-3 col-form-label font-weight-bold text-right">Email</label>
      <div class="col-sm-9">
        <input type="email" class="form-control" id="email" name="email" placeholder="Email Address" value="{{Auth::user()->email}}" readonly>
      </div>
    </div>
    <hr>
    <div class="form-group row">
      <div class="col-sm-9">
        <button type="submit" class="btn btn-primary">Submit</button>
      </div>
    </div>
  </form>

@endsection