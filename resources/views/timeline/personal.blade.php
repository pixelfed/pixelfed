@extends('layouts.app')

@push('scripts')
<script type="text/javascript" src="{{mix('js/timeline.js')}}"></script>
@endpush 

@section('content')

<div class="container">
  <div class="col-12 col-md-8 offset-md-2 pt-4">
    @if ($errors->any())
      <div class="alert alert-danger">
          <ul>
              @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
              @endforeach
          </ul>
      </div>
    @endif

    @include('timeline.partial.new-form')
    
    <div class="timeline-feed my-5" data-timeline="personal">
    @foreach($timeline as $item)

      @include('status.template')

    @endforeach
    @if($timeline->count() == 0)
    <div class="card">
      <div class="card-body py-5">
        <div class="d-flex justify-content-center align-items-center">
          <p class="lead font-weight-bold mb-0">{{ __('timeline.emptyPersonalTimeline') }}</p>
        </div>
      </div>
    </div>
    @endif
    </div>

    <div class="page-load-status">
      <div class="infinite-scroll-request">
        <div class="d-none fixed-top loading-page"></div>
      </div>
      <div class="infinite-scroll-last">
        <h3>No more content</h3>
        <p class="text-muted">
          Maybe you could try 
          <a href="{{route('discover')}}">discovering</a>
          more people you can follow.
        </p>
      </div>
      <div class="infinite-scroll-error">
        <h3>Whoops, an error</h3>
        <p class="text-muted">
          Try reloading the page
        </p>
      </div>
    </div>

    <div class="d-flex justify-content-center">
      {{$timeline->links()}}
    </div>

  </div>
</div>


@endsection
