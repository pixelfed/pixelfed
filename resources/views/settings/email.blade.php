@extends('layouts.app')

@section('content')
@if (session('status'))
    <div class="alert alert-primary px-3 h6 text-center">
        {{ session('status') }}
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger px-3 h6 text-center">
            @foreach($errors->all() as $error)
                <p class="font-weight-bold mb-1">{{ $error }}</p>
            @endforeach
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger px-3 h6 text-center">
        {{ session('error') }}
    </div>
@endif

<div class="container">
  <div class="col-12">
    <div class="card shadow-none border mt-5">
      <div class="card-body">
        <div class="row">
          <div class="col-12 p-3 p-md-5">
			  <div class="title">
			    <h3 class="font-weight-bold">Email Settings</h3>
			  </div>
			  <hr>
			  <form method="post" action="{{route('settings.email')}}">
			    @csrf
			    <input type="hidden" class="form-control" name="name" value="{{Auth::user()->profile->name}}">
			    <input type="hidden" class="form-control" name="username" value="{{Auth::user()->profile->username}}">
			    <input type="hidden" class="form-control" name="website" value="{{Auth::user()->profile->website}}">

			    <div class="form-group">
			      <label for="email" class="font-weight-bold">Email Address</label>
			        <input type="email" class="form-control" id="email" name="email" placeholder="Email Address" value="{{Auth::user()->email}}">
			        <p class="help-text small text-muted font-weight-bold">
			          @if(Auth::user()->email_verified_at)
			          <span class="text-success">Verified</span> {{Auth::user()->email_verified_at->diffForHumans()}}
			          @else
			          <span class="text-danger">Unverified</span> You need to <a href="/i/verify-email">verify your email</a>.
			          @endif
			        </p>
			    </div>
			    <div class="form-group row">
			      <div class="col-12 text-right">
			        <button type="submit" class="btn btn-primary font-weight-bold py-0 px-5">Submit</button>
			      </div>
			    </div>
			  </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


@endsection
