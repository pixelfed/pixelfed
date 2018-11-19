@extends('layouts.app')

@section('content')

<div class="container">
  <div class="col-12">
    <div class="card mt-5">
      <div class="card-body p-0">
        <div class="row">
          @include('settings.partial.sidebar')
          <div class="col-12 col-md-9 p-5">
            @if (session('status'))
                <div class="alert alert-success font-weight-bold">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('errors'))
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach (session('errors') as $error)
                            <li class="font-weight-bold">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('section')
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection