@extends('layouts.app')

@section('content')
<div class="container mt-5 pt-3">
  <section>
    <p class="lead text-muted font-weight-bold">Discover People</p>
    <div class="row">
      @foreach($people as $profile)
      <div class="col-4 p-0 p-sm-2 p-md-3">
        <div class="card card-md-border-0">
          <div class="card-body p-4 text-center">
            <div class="avatar pb-3">
              <a href="{{$profile->url()}}">
                <img src="{{$profile->avatarUrl()}}" class="img-thumbnail rounded-circle" width="64px">
              </a>
            </div>
            <p class="lead font-weight-bold mb-0 text-truncate"><a href="{{$profile->url()}}" class="text-dark">{{$profile->username}}</a></p>
            <p class="text-muted text-truncate">{{$profile->name}}</p>
            <form class="follow-form" method="post" action="/i/follow" data-id="{{$profile->id}}" data-action="follow">
              @csrf
              <input type="hidden" name="item" value="{{$profile->id}}">
              <button class="btn btn-primary font-weight-bold px-4 py-0" type="submit">Follow</button>
            </form>
          </div>
        </div>
      </div>
      @endforeach

      @if($people->count() == 0)
      <div class="col-12 text-center text-muted">
          <h4 class="font-weight-bold">No results found</h4>
      </div>
      @endif

    </div>
  </section>
  <section class="pt-5 mt-5">
    <p class="lead text-muted font-weight-bold">Explore</p>
    <div class="profile-timeline row">
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

      @if($posts->count() == 0)
      <div class="col-12 text-center text-muted">
          <h4 class="font-weight-bold">No results found</h4>
      </div>
      @endif

    </div>
  </section>
</div>

@endsection

@push('meta')
<meta property="og:description" content="Discover People!">
@endpush
