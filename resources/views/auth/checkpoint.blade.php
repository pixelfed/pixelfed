@extends('layouts.blank')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="text-center">
                <img src="/img/pixelfed-icon-color.svg" height="60px">
                <p class="font-weight-light h3 py-4">Verify 2FA Code to continue</p>
            </div>
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        @csrf

                        <div class="form-group row">

                            <div class="col-md-12">
                                <input id="code" type="text" class="form-control{{ $errors->has('code') ? ' is-invalid' : '' }}" name="code" placeholder="{{__('Two-Factor Authentication Code')}}" required autocomplete="off" autofocus="">

                                @if ($errors->has('code'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('code') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if(config('pixelfed.recaptcha'))
                        <div class="row my-3">
                            {!! Recaptcha::render() !!}
                        </div>
                        @endif

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
        </div>
    </div>
</div>
@endsection
