@extends('layouts.app')

@section('content')
<div class="profile-header bg-light" style="background: #FF5F6D;  /* fallback for old browsers */
background: -webkit-linear-gradient(to right, #FFC371, #FF5F6D);  /* Chrome 10-25, Safari 5.1-6 */
background: linear-gradient(to right, #FFC371, #FF5F6D); /* W3C, IE 10+/ Edge, Firefox 16+, Chrome 26+, Opera 12+, Safari 7+ */
">
  <div class="container py-5">
    <div class="profile-details text-white">
      <div class="username-bar">
        <p class="display-3 font-weight-lighter mb-0">My Discover</p>
        <p class="lead font-weight-lighter">Discover posts based on hashtags you've used before</p>
      </div>
    </div>
  </div>
</div>

<div class="container">
  <div class="pt-4 d-flex justify-content-between align-items-center">
    <p>
      <span class="font-weight-lighter pr-3">Related hashtags:</span> 
      @foreach($tags as $hashtag)
        <a class="btn btn-outline-secondary btn-sm py-0 pr-2" href="{{$hashtag->url()}}">#{{$hashtag->name}}</a>
      @endforeach
    </p>
    <p class="font-weight-lighter">
      {{$posts->post_count}} posts
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
  <div class="d-flex justify-content-center pagination-container mt-4">
    {{$posts->links()}}
  </div>
</div>

@endsection

@push('scripts')
<script type="text/javascript">

  $(document).ready(function() {
    $('.pagination-container').hide();
    $('.pagination').hide();
    let elem = document.querySelector('.tag-timeline');
    let infScroll = new InfiniteScroll( elem, {
      path: '.pagination__next',
      append: '.tag-timeline',
      status: '.page-load-status',
      history: true,
    });
  });

</script>
@endpush