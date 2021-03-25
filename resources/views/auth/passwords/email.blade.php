@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header bg-white p-3 text-center font-weight-bold">{{ __('Reset Password') }}</div>

                <div class="card-body">
                    @if (session('status') || $errors->has('email'))
                        <div class="alert alert-success">
                            {{ session('status') ?? $errors->first('email') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="form-group row">
                            <div class="col-md-12">
                                <input id="email" type="email" class="form-control" name="email" placeholder="{{ __('E-Mail') }}" value="{{ old('email') }}" required>
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary btn-block py-0 font-weight-bold">
                                    {{ __('Send Password Reset Link') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body text-center">
                    <a class="btn btn-link font-weight-bold" href="{{ route('login') }}">
                        {{ __('Back to Login') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
