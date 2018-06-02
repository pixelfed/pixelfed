@extends('layouts.app')

@section('content')

<div class="container px-0 mt-md-4">
  <div class="card status-container orientation-{{$status->firstMedia()->orientation ?? 'unknown'}}">
    <div class="row mx-0">
      <div class="col-12 col-md-8 status-photo px-0">
        <img src="{{$status->mediaUrl()}}" width="100%">
      </div>
      <div class="col-12 col-md-4 px-0 d-flex flex-column">
        <div class="d-flex align-items-center justify-content-between card-header">
          <div class="d-flex align-items-center status-username">
            <div class="status-avatar mr-2">
              <img class="img-thumbnail" src="{{$user->avatarUrl()}}" width="24px" height="24px" style="border-radius:12px;">
            </div>
            <div class="username">
              <a href="{{$user->url()}}" class="username-link font-weight-bold text-dark">{{$user->username}}</a>
            </div>
          </div>
          <div class="timestamp mb-0">
            <p class="small text-uppercase mb-0"><a href="{{$status->url()}}" class="text-muted">{{$status->created_at->diffForHumans()}}</a></p>
          </div>
        </div>
        <div class="card-body status-comments">
          <div class="status-comment">
            <p class="mb-1">
              <span class="font-weight-bold pr-1">{{$status->profile->username}}</span>
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
        <div class="card-body flex-grow-0">
          <div class="reactions h3 mb-0">
            <form class="d-inline-flex like-form pr-3" method="post" action="/i/like" style="display: inline;" data-id="{{$status->id}}" data-action="like">
              @csrf
              <input type="hidden" name="item" value="{{$status->id}}">
              <button class="btn btn-link text-dark p-0" type="submit"><h3 class="icon-heart mb-0"></h3></button>
            </form>
            <span class="icon-speech pr-3"></span>
            @if(Auth::check())
            @if(Auth::user()->profile->id === $status->profile->id || Auth::user()->is_admin == true)
            <form method="post" action="/i/delete" class="d-inline-flex">
              @csrf
              <input type="hidden" name="type" value="post">
              <input type="hidden" name="item" value="{{$status->id}}">
              <button type="submit" class="btn btn-link text-dark p-0"><h3 class="icon-trash mb-0"></h3></button>
            </form>
            @endif
            @endif
            <span class="float-right">
              <form class="d-inline-flex bookmark-form" method="post" action="/i/bookmark" style="display: inline;" data-id="{{$status->id}}" data-action="bookmark">
                @csrf
                <input type="hidden" name="item" value="{{$status->id}}">
                <button class="btn btn-link text-dark p-0" type="submit"><h3 class="icon-notebook mb-0"></h3></button>
              </form>
            </span>
          </div>
          <div class="likes font-weight-bold mb-0">
            <span class="like-count">{{$status->likes()->count()}}</span> likes
          </div>
        </div>
        <div class="card-footer">
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

@endsection
