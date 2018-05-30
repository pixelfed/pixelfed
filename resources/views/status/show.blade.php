@extends('layouts.app')

@section('content')

<div class="container">
  <div class="col-12 mt-4">
    
    <div class="card status-container orientation-{{$status->firstMedia()->orientation ?? 'unknown'}}">
      <div class="card-body p-0">
        <div class="row">
          <div class="col-12 col-md-8 status-photo">
            <img src="{{$status->mediaUrl()}}" width="100%">
          </div>
          <div class="col-12 col-md-4" style="height:100%">
            <div class="status-username d-inline-flex align-items-center pr-3 pt-3">
              <div class="status-avatar mr-2">
                <img class="img-thumbnail" src="{{$user->avatarUrl()}}" width="50px" height="50px" style="border-radius:40px;">
              </div>
              <div class="username">
                <a href="{{$user->url()}}" class="username-link font-weight-bold text-dark">{{$user->username}}</a>
              </div>
            </div>
            <hr>
            <div class="pr-3 mb-2 status-comments">
              <div class="status-comment">
                <span class="font-weight-bold pr-1">{{$status->profile->username}}</span>
                <p class="mb-1">
                <span class="comment-text">{!! $status->rendered ?? e($status->caption) !!}</span>
              </p>
              <div class="comments">
              @foreach($status->comments->reverse()->take(10) as $item)
                <p class="mb-0">
                  <span class="font-weight-bold pr-1"><bdi><a class="text-dark" href="{{$item->profile->url()}}">{{$item->profile->username}}</a></bdi></span>
                  <span class="comment-text">{!!$item->rendered!!} <a href="{{$item->url()}}" class="text-dark small font-weight-bold float-right">{{$item->created_at->diffForHumans(null, true, true ,true)}}</a></span>
                </p>
              @endforeach
              </div>
              </div>
            </div>
            <div>
            <div class="reactions h3 pr-3 mb-0">
            <form class="like-form pr-3" method="post" action="/i/like" style="display: inline;" data-id="{{$status->id}}" data-action="like">
              @csrf
              <input type="hidden" name="item" value="{{$status->id}}">
              <button class="btn btn-link text-dark p-0" type="submit"><span class="icon-heart" style="font-size:25px;"></span></button>
            </form>
              <span class="icon-speech"></span>
              <span class="float-right">
                <span class="icon-notebook"></span>
              </span>
            </div>
            <div class="likes font-weight-bold mb-0">
              <span class="like-count">{{$status->likes()->count()}}</span> likes
            </div>
            <div class="timestamp mb-0">
              <p class="small text-uppercase mb-0"><a href="{{$status->url()}}" class="text-muted">{{$status->created_at->diffForHumans()}}</a></p>
            </div>
            <hr class="my-2">
            <div class="pr-3 pb-2">
              <form class="comment-form" method="post" action="/i/comment" data-id="{{$status->id}}" data-truncate="false">
                @csrf
                <input type="hidden" name="item" value="{{$status->id}}">
                <input class="form-control" name="comment" placeholder="Add a comment...">
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection