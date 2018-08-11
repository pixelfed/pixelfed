@extends('layouts.app')

@section('content')

<div class="container">
  
  <div class="profile-header row my-5">
    <div class="col-12 col-md-3">
      <div class="profile-avatar">
        <img class="rounded-circle card" src="{{$posts->last()->thumb()}}" width="172px" height="172px">
      </div>
    </div>
    <div class="col-12 col-md-9 d-flex align-items-center">
      <div class="profile-details">
        <div class="username-bar pb-2  d-flex align-items-center">
          <span class="h1">{{$tag->name}}</span>
        </div>
        <p class="font-weight-bold">
          {{$tag->posts_count}} posts
        </p>
      </div>
    </div>
  </div>

  <div class="tag-timeline row">
    @foreach($posts as $status)
    <div class="col-4 p-0 p-sm-2 p-md-3">
      <a class="card info-overlay card-md-border-0" href="{{$status->url()}}">
        <div class="square {{$status->firstMedia()->filter_class}}">
          <div class="square-content" style="background-image: url('{{$status->thumb()}}')"></div>
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
  <div class="d-flex justify-content-center pagination-container mt-4">
    {{$posts->links()}}
  </div>
</div>

@endsection

@push('meta')
<meta property="og:description" content="Discover {{$tag->name}}">
@endpush

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
