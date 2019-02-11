@extends('layouts.app')

@section('content')

@include('admin.partial.topnav')

<div class="container">
  <div class="col-12 mt-5">
    <div class="card">
      <div class="card-body p-0">
        <div class="row">
          @include('admin.partial.sidebar')
          <div class="col-12 col-md-9 p-4">
            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status')}}
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