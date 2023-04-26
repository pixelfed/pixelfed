@extends('layouts.blank')

@push('styles')
<link href="{{ mix('css/landing.css') }}" rel="stylesheet">
<link rel="preload" as="image" href="{{ url('/_landing/bg.jpg')}}" />
@endpush

@section('content')
<div class="page-wrapper">
	<div class="container mt-5">
	    <div class="row justify-content-center">
	        <div class="col-lg-5">
	            <div class="text-center">
	            	<a href="/">
	                	<img src="/img/pixelfed-icon-white.svg" height="60px">
	            	</a>
	                <h1 class="pt-4 pb-1">2FA Checkpoint</h1>
		            <p class="font-weight-light lead">
		            	Enter the 2FA code from your device.
		            </p>
		            <p class="text-muted small pb-3">
		                If you lose access to your 2FA device, contact the admins.
		            </p>
	            </div>
	            <div class="card bg-glass">
	                <div class="card-body">
	                    <form method="POST">
	                        @csrf

	                        <div class="form-group row">

	                            <div class="col-md-12">
	                                <input id="code" type="text" class="form-control{{ $errors->has('code') ? ' is-invalid' : '' }}" name="code" placeholder="{{__('Two-Factor Authentication Code')}}" required autocomplete="off" autofocus="" inputmode="numeric" minlength="6">

	                                @if ($errors->has('code'))
	                                    <span class="invalid-feedback">
	                                        <strong>{{ $errors->first('code') }}</strong>
	                                    </span>
	                                @endif
	                            </div>
	                        </div>

	                        <div class="form-group row mb-0">
	                            <div class="col-md-12">
	                                <button type="submit" class="btn btn-success btn-block rounded-pill font-weight-bold">
	                                    {{ __('Verify') }}
	                                </button>

	                            </div>
	                        </div>
	                    </form>
	                </div>
	            </div>
	           	<div class="d-flex justify-content-between my-3">
	            	<p class="mb-0 small">
	            		<span class="text-muted">Logged in as:</span> {{request()->user()->username}}
	            	</p>

	                <form action="/logout" method="post">
	            		@csrf
	            		<button type="submit" class="btn btn-link p-0 btn-sm text-white font-weight-bold">Logout</button>
	            	</form>
	            </div>
	        </div>
	    </div>
	</div>
</div>
@endsection
