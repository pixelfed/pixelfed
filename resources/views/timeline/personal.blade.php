@extends('layouts.app')

@push('scripts')
<script type="text/javascript" src="{{mix('js/timeline.js')}}"></script>
@endpush 

@section('content')

<div class="container p-0">
  <div class="col-md-10 col-lg-8 mx-auto pt-4 px-0">
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
    <div class="card card-md-rounded-0">
      <div class="card-body py-5">
        <div class="d-flex justify-content-center align-items-center">
          <p class="lead font-weight-bold mb-0">{{ __('timeline.emptyPersonalTimeline') }}</p>
        </div>
      </div>
    </div>
    @endif
    </div>

    <div class="page-load-status" style="display: none;">
      <div class="infinite-scroll-request" style="display: none;">
        <div class="fixed-top loading-page"></div>
      </div>
      <div class="infinite-scroll-last" style="display: none;">
        <h3>No more content</h3>
        <p class="text-muted">
          Maybe you could try 
          <a href="{{route('discover')}}">discovering</a>
          more people you can follow.
        </p>
      </div>
      <div class="infinite-scroll-error" style="display: none;">
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
