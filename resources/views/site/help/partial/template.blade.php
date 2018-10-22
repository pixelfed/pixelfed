@extends('layouts.anon', ['title' => 'Pixelfed Help Center'])

@section('content')

<div class="container">
  <div class="col-12">
    <div class="card mt-5">
      <div class="card-header font-weight-bold text-muted bg-white py-4">
        <a href="{{route('site.help')}}" class="text-muted">Help Center</a>
        <span class="px-2 font-weight-light">&mdash;</span>
        {{ $breadcrumb ?? ''}}
      </div>
      <div class="card-body p-0">
        <div class="row">
          @include('site.help.partial.sidebar')
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
