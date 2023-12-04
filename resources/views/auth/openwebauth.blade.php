@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="">
                <div class="card-header bg-transparent p-3 text-center font-weight-bold h3">{{ __('Remote OpenWeb Authentication') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('openwebauth') }}">
                        @csrf

                        <div class="form-group row">

                            <div class="col-md-12">
                                <input name="handle" type="email" class="form-control" placeholder="{{__('Fediverse handle e.g. me@example.org')}}" required autofocus>
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary btn-block btn-lg font-weight-bold">
                                    {{ __('Enter') }}
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