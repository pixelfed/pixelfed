@extends('layouts.app')

@section('content')

<noscript>
  <div class="container">
    <div class="card border-left-primary mt-5">
      <div class="card-body">
        <p class="mb-0 font-weight-bold">Javascript is required for an optimized experience, please enable it or use the <a href="#">lite</a> version.</p>
      </div>
    </div>
  </div>
</noscript>

<div class="container p-0 d-none timeline-container">
  <div class="row">
    <div class="col-md-10 col-lg-8 mx-auto pt-4 px-0 my-3 pr-2">
        @if (session('status'))
            <div class="alert alert-success">
                <span class="font-weight-bold">{!! session('status') !!}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">
                <span class="font-weight-bold">{!! session('error') !!}</span>
            </div>
        @endif

      
      <div class="timeline-feed" data-timeline="{{$type}}">

      @foreach($timeline as $item)
        @if(is_null($item->in_reply_to_id))
        @include('status.template')
        @endif
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
    <div class="col-md-2 col-lg-4 pt-4 my-3">

      <div class="media d-flex align-items-center mb-4">
        <a href="{{Auth::user()->profile->url()}}">
          <img class="mr-3 rounded-circle box-shadow" src="{{Auth::user()->profile->avatarUrl()}}" alt="{{Auth::user()->username}}'s avatar" width="64px">
        </a>
        <div class="media-body">
          <p class="mb-0 px-0 font-weight-bold"><a href="{{Auth::user()->profile->url()}}">&commat;{{Auth::user()->username}}</a></p>
          <p class="mb-0 small text-muted">{{Auth::user()->name}}</p>
        </div>
      </div>

      <follow-suggestions></follow-suggestions>

      <footer>
        <div class="container pb-5">
            <p class="mb-0 text-uppercase font-weight-bold text-muted small">
              <a href="{{route('site.about')}}" class="text-dark pr-2">About Us</a>
              <a href="{{route('site.help')}}" class="text-dark pr-2">Support</a>
              <a href="{{route('site.opensource')}}" class="text-dark pr-2">Open Source</a>
              <a href="{{route('site.language')}}" class="text-dark pr-2">Language</a>
              <a href="{{route('site.terms')}}" class="text-dark pr-2">Terms</a>
              <a href="{{route('site.privacy')}}" class="text-dark pr-2">Privacy</a>
              <a href="{{route('site.platform')}}" class="text-dark pr-2">API</a>
              <a href="#" class="text-dark pr-2">Directory</a>
              <a href="#" class="text-dark pr-2">Profiles</a>
              <a href="#" class="text-dark">Hashtags</a>
            </p>
            <p class="mb-0 text-uppercase font-weight-bold text-muted small">
              <a href="http://pixelfed.org" class="text-muted" rel="noopener">Powered by PixelFed</a>
            </p>
        </div>
      </footer>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script type="text/javascript" src="{{mix('js/timeline.js')}}"></script>
@endpush 