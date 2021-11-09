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
                <p class="font-weight-bold mb-1">{{ $error }}</li>
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
			    <h3 class="font-weight-bold">Update Password</h3>
			  </div>
			  <hr>
			  <form method="post">
			    @csrf
			    <div class="form-group row">
			      <label for="existing" class="col-sm-3 col-form-label font-weight-bold">Current</label>
			      <div class="col-sm-9">
			        <input type="password" class="form-control" name="current" placeholder="Your current password">
			      </div>
			    </div>
			    <hr>
			    <div class="form-group row">
			      <label for="new" class="col-sm-3 col-form-label font-weight-bold">New</label>
			      <div class="col-sm-9">
			        <input type="password" class="form-control" name="password" placeholder="Enter new password here">
			      </div>
			    </div>
			    <div class="form-group row">
			      <label for="confirm" class="col-sm-3 col-form-label font-weight-bold">Confirm</label>
			      <div class="col-sm-9">
			        <input type="password" class="form-control" name="password_confirmation" placeholder="Confirm new password">
			      </div>
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
