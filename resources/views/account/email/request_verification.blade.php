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
	<div class="card shadow-none border">
	  <div class="card-header font-weight-bold bg-white">Request Manual Email Verification</div>
	  <div class="card-body">
	  	<p class="">If you are experiencing issues receiving your email address confirmation code to the email address you registered with, you can request manual verification as a last resort. An administrator will review your request.</p>

	  	<p class="font-weight-bold">If you request manual email verification, you still may experience issues recieving emails from our service, including password reset requests.</p>

	  	<p>In the event you need to reset your password and do not receive the password reset email, please contact the administrators.</p>

	  	@if(!$exists)
	  	<form method="post">
          @csrf
          <button type="submit" class="btn btn-primary btn-block py-1 font-weight-bold">I understand, proceed with request</button>
        </form>
        @else
        <button class="btn btn-primary btn-block py-1 font-weight-bold" disabled>Verification Request Sent</button>
        @endif
	  </div>
  </div>
</div>
</div>
@endsection
