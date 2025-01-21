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
            <div class="card-body p-0">
                <div class="row">
                    @include('settings.partial.sidebar')
                    <div class="col-12 col-md-9 p-5">
                        @yield('section')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
