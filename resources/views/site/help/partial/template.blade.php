@extends('layouts.anon', ['title' => 'Pixelfed Help Center'])

@section('content')

<div class="container px-0 mt-0 mt-md-4 mb-md-5 pb-md-5">
  <div class="col-12 px-0">
    <div class="card mt-md-5 px-0 mx-md-3 shadow-none border">
      <div class="card-header font-weight-bold text-muted bg-white py-4">
        <a href="{{route('site.help')}}" class="text-muted">{{__('helpcenter.helpcenter')}}</a>
        <span class="px-2 font-weight-light">&mdash;</span>
        {{ $breadcrumb ?? ''}}
      </div>
      <div class="card-body p-0">
        <div class="row px-0">
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
