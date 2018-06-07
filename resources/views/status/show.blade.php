@extends('layouts.app',['title' => $user->username . " posted a photo: " . $status->likes_count . " likes, " . $status->comments_count . " comments" ])

@section('content')

<div class="container px-0 mt-md-4">
  <div class="card status-container orientation-{{$status->firstMedia()->orientation ?? 'unknown'}}">
    <div class="row mx-0">
    <div class="d-flex d-md-none align-items-center justify-content-between card-header w-100">
      <div class="d-flex align-items-center status-username">
        <div class="status-avatar mr-2">
          <img class="img-thumbnail" src="{{$user->avatarUrl()}}" width="24px" height="24px" style="border-radius:12px;">
        </div>
        <div class="username">
          <a href="{{$user->url()}}" class="username-link font-weight-bold text-dark">{{$user->username}}</a>
        </div>
      </div>
      <div class="timestamp mb-0">
        <p class="small text-uppercase mb-0"><a href="{{$status->url()}}" class="text-muted">{{$status->created_at->diffForHumans(null, true, true, true)}}</a></p>
       </div>
     </div>
      <div class="col-12 col-md-8 status-photo px-0">
        <img src="{{$status->mediaUrl()}}" width="100%">
      </div>
      <div class="col-12 col-md-4 px-0 d-flex flex-column border-left border-md-left-0">
        <div class="d-md-flex d-none align-items-center justify-content-between card-header">
          <div class="d-flex align-items-center status-username">
            <div class="status-avatar mr-2">
              <img class="img-thumbnail" src="{{$user->avatarUrl()}}" width="24px" height="24px" style="border-radius:12px;">
            </div>
            <div class="username">
              <a href="{{$user->url()}}" class="username-link font-weight-bold text-dark">{{$user->username}}</a>
            </div>
          </div>
          <div class="timestamp mb-0">
            <p class="small text-uppercase mb-0"><a href="{{$status->url()}}" class="text-muted">{{$status->created_at->diffForHumans(null, true, true, true)}}</a></p>
          </div>
        </div>
        <div class="d-flex flex-md-column flex-column-reverse h-100">
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
                <button class="btn btn-link text-dark btn-lg p-0" type="submit" title="Like!">
                  <span class="far fa-heart fa-lg mb-0"></span>
                </button>
              </form>
              <span class="far fa-comment pt-1 pr-3" title="Comment"></span>
              @if(Auth::check())
              @if(Auth::user()->profile->id === $status->profile->id || Auth::user()->is_admin == true)
              <form method="post" action="/i/delete" class="d-inline-flex">
                @csrf
                <input type="hidden" name="type" value="post">
                <input type="hidden" name="item" value="{{$status->id}}">
                <button type="submit" class="btn btn-link btn-lg text-dark p-0" title="Remove">
                  <span class="far fa-trash-alt fa-lg mb-0"></span>
                </button>
              </form>
              @endif
              @endif
              <span class="float-right">
                <form class="d-inline-flex bookmark-form" method="post" action="/i/bookmark" style="display: inline;" data-id="{{$status->id}}" data-action="bookmark">
                  @csrf
                  <input type="hidden" name="item" value="{{$status->id}}">
                  <button class="btn btn-link text-dark p-0 btn-lg" type="submit" title="Save">
                    <span class="far fa-bookmark fa-lg mb-0"></span>
                  </button>
                </form>
              </span>
            </div>
            <div class="likes font-weight-bold mb-0">
              <span class="like-count" data-count="{{$status->likes_count}}">{{$status->likes_count}}</span> likes
            </div>
          </div>
        </div>
        <div class="card-footer bg-light sticky-md-bottom">
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

@push('meta')
<meta property="og:description" content="{!! $status->rendered ?? e($status->caption) !!}">
<meta property="og:image" content="{{$status->mediaUrl()}}">
@endpush
