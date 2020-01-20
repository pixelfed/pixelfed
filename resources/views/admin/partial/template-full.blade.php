@extends('layouts.app')

@section('content')

@include('admin.partial.topnav')

<div class="container">
  <div class="col-12 mt-4">
    <div class="card shadow-none bg-transparent">
      <div class="card-body p-0">
        <div class="row">
          <div class="col-12">
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