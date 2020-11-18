@extends('layouts.app')

@section('content')

<div class="profile-header bg-light bgd-2">
  <div class="container py-5">
    <div class="row">
      <div class="col-12 col-md-3">
        <div class="profile-avatar">
          <img class="rounded-circle card" src="{{$tag->thumb()}}" width="172" height="172">
        </div>
      </div>
      <div class="col-12 col-md-9 d-flex align-items-center">
        <div class="profile-details text-white">
          <div class="username-bar d-flex align-items-center">
            <span class="display-3 font-weight-lighter">{{$tag->name}}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="container">
  <div class="pt-4 d-flex justify-content-between align-items-center">
    <p>
      @if($tag->hashtags->count() > 0)
      <span class="font-weight-lighter pr-3">Related hashtags:</span> 
      @foreach($tag->hashtags as $hashtag)
        <a class="btn btn-outline-secondary btn-sm py-0 pr-2" href="{{$hashtag->url()}}">#{{$hashtag->name}}</a>
      @endforeach
      @endif
    </p>
    <p class="font-weight-lighter">
      {{$tag->posts_count}} posts
    </p>
  </div>
  <div class="tag-timeline">
    <div class="row">
      @foreach($posts as $status)
      <div class="col-4 p-0 p-sm-2 p-md-3">
        <a class="card info-overlay card-md-border-0" href="{{$status->url()}}">
          <div class="square {{$status->firstMedia()->filter_class}}">
            <div class="square-content" style="background-image: url('{{$status->thumb()}}');background-size:cover;"></div>
            <div class="info-overlay-text">
              <h5 class="text-white m-auto font-weight-bold">
                <span class="pr-4">
                <span class="far fa-heart fa-lg pr-1"></span> {{$status->likes_count}}
                </span>
                <span>
                <span class="far fa-comment fa-lg pr-1"></span> {{$status->comments_count}}
                </span>
              </h5>
            </div>
          </div>
        </a>
      </div>
      @endforeach
    </div>
  </div>
</div>

@endsection

@push('meta')
<meta property="og:description" content="Discover {{$tag->name}}">
<style type="text/css">
  .bgd-1 {
    background: #141E30;  /* fallback for old browsers */
    background: -webkit-linear-gradient(to right, #243B55, #141E30);  /* Chrome 10-25, Safari 5.1-6 */
    background: linear-gradient(to right, #243B55, #141E30); /* W3C, IE 10+/ Edge, Firefox 16+, Chrome 26+, Opera 12+, Safari 7+ */
  }

  .bgd-2 {
    background: #ec008c;  /* fallback for old browsers */
    background: -webkit-linear-gradient(to right, #fc6767, #ec008c);  /* Chrome 10-25, Safari 5.1-6 */
    background: linear-gradient(to right, #fc6767, #ec008c); /* W3C, IE 10+/ Edge, Firefox 16+, Chrome 26+, Opera 12+, Safari 7+ */
  }
  .bgd-3 {
    background: #11998e;  /* fallback for old browsers */
    background: -webkit-linear-gradient(to right, #38ef7d, #11998e);  /* Chrome 10-25, Safari 5.1-6 */
    background: linear-gradient(to right, #38ef7d, #11998e); /* W3C, IE 10+/ Edge, Firefox 16+, Chrome 26+, Opera 12+, Safari 7+ */
  }
</style>
@endpush

@push('scripts')
<script type="text/javascript" src="{{ mix('js/compose.js') }}"></script>
<script type="text/javascript">
$(document).ready(function(){new Vue({el: '#content'});});
</script>
@endpush