@extends('layouts.anon',['title' => 'About ' . config('app.name')])

@section('content')

<div class="container px-0 mt-0 mt-md-4 mb-md-5 pb-md-5">
  <div class="col-12 px-0">
    <div class="card mt-md-5">
      <div class="card-body p-0">
        <div class="row px-0">
          @include('site.partial.sidebar')
          <div class="col-12 col-md-9 p-5">
            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
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
