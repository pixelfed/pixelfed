@extends('layouts.blank')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="text-center">
                <img src="/img/pixelfed-icon-color.svg" height="60px">
                <p class="font-weight-light h3 py-4">{{__('auth.confirmPasswordContinue')}}</p>
            </div>
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        @csrf

                        <div class="form-group">
                            <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" placeholder="{{__('auth.password')}}" required>

                            @if ($errors->has('password'))
                                <span class="invalid-feedback">
                                    <strong>{{ $errors->first('password') }}</strong>
                                </span>
                            @endif
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                              <input type="checkbox" class="custom-control-input" id="trusted-device" name="trustDevice">
                              <label class="custom-control-label text-muted" for="trusted-device">{{__('auth.trustDevice')}}</label>
                            </div>
                        </div>  

                        <div class="form-group row mb-0">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-success btn-block  font-weight-bold">
                                    {{ __('auth.confirmPassword') }}
                                </button>

                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
