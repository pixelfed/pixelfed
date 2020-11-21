@extends('layouts.app')

@section('content')
<div class="container mt-4">
  <div class="col-12 col-md-8 offset-md-2">
    @if (session('status'))
        <div class="alert alert-success">
            <p class="font-weight-bold mb-0">{{ session('status') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">
            <p class="font-weight-bold mb-0">{{ session('error') }}</p>
        </div>
    @endif
    <div class="card">
      <div class="card-header font-weight-bold bg-white">Confirm Email Address</div>
      <div class="card-body">
        <p class="lead">You need to confirm your email address (<span class="font-weight-bold">{{Auth::user()->email}}</span>) before you can proceed.</p>
        <hr>
        <form method="post">
          @csrf
          <button type="submit" class="btn btn-primary btn-block py-1 font-weight-bold">Send Confirmation Email</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection