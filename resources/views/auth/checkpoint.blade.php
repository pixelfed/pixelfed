@extends('layouts.blank')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="text-center">
                <img src="/img/pixelfed-icon-color.svg" height="60px">
                <p class="font-weight-light h3 py-4">Verify Two Factor Code</p>
            </div>
            <div class="alert alert-info small">
                If you lose access to your 2FA device, contact the admins.
            </div>
            <div class="card">
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
                                <button type="submit" class="btn btn-success btn-block  font-weight-bold">
                                    {{ __('Verify') }}
                                </button>

                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="d-flex justify-content-between pt-4 small">
                <a class="text-lighter text-decoration-none" href="/{{Auth::user()->username}}">Logged in as: <span class="font-weight-bold text-muted">{{Auth::user()->username}}</span></a>
                <span>
                    <a class="text-decoration-none text-muted font-weight-bold" href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();">Logout</a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </span>
            </div>
        </div>
    </div>
</div>
@endsection
