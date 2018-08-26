<div class="card mb-4 status-card card-md-rounded-0" data-id="{{$item->id}}" data-comment-max-id="0">
  <div class="card-header d-inline-flex align-items-center bg-white">
    <img src="{{$item->profile->avatarUrl()}}" width="32px" height="32px" style="border-radius: 32px;">
    <a class="username font-weight-bold pl-2 text-dark" href="{{$item->profile->url()}}">
      {{$item->profile->username}}
    </a>
    <div class="text-right" style="flex-grow:1;">
      <div class="dropdown">
        <button class="btn btn-link text-dark no-caret dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Post options">
        <span class="fas fa-ellipsis-v fa-lg text-muted"></span>
        </button>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
          <a class="dropdown-item font-weight-bold" href="{{$item->url()}}">Go to post</a>
          <a class="dropdown-item font-weight-bold" href="{{route('report.form')}}?type=post&id={{$item->id}}">Report</a>
          <a class="dropdown-item font-weight-bold" href="#">Embed</a>
        @if(Auth::check())
          @if(Auth::user()->profile->id === $item->profile->id || Auth::user()->is_admin == true)
          <a class="dropdown-item font-weight-bold" href="{{$item->editUrl()}}">Edit</a>
          <form method="post" action="/i/delete">
            @csrf
            <input type="hidden" name="type" value="post">
            <input type="hidden" name="item" value="{{$item->id}}">
            <button type="submit" class="dropdown-item btn btn-link text-danger font-weight-bold">Delete</button>
          </form>
          @endif
        @endif

        </div>
      </div>
    </div>
  </div>
  @if($item->is_nsfw)
  <details class="details-animated">
      <summary>
        <p class="mb-0 px-3 lead font-weight-bold">Content Warning: This may contain potentially sensitive content.</p>
        <p class="font-weight-light">(click to show)</p>
      </summary>
      <a class="max-hide-overflow {{$item->firstMedia()->filter_class}}" href="{{$item->url()}}">
        <img class="card-img-top lazy" src="data:image/gif;base64,R0lGODlhAQABAIAAAMLCwgAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==" data-src="{{$item->mediaUrl()}}" data-srcset="{{$item->mediaUrl()}} 1x">
      </a>
  </details>
  @else
  <a class="max-hide-overflow {{$item->firstMedia()->filter_class}}" href="{{$item->url()}}">
    @if($loop->index < 2)
    <img class="card-img-top" src="{{$item->mediaUrl()}}" data-srcset="{{$item->mediaUrl()}} 1x">
    @else
    <img class="card-img-top lazy" src="data:image/gif;base64,R0lGODlhAQABAIAAAMLCwgAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==" data-src="{{$item->mediaUrl()}}" data-srcset="{{$item->mediaUrl()}} 1x">
    @endif
  </a>
  @endif
  <div class="card-body">
    <div class="reactions my-1">
      <form class="d-inline-flex like-form pr-3" method="post" action="/i/like" style="display: inline;" data-id="{{$item->id}}" data-action="like" data-count="{{$item->likes_count}}">
        @csrf
        <input type="hidden" name="item" value="{{$item->id}}">
        <button class="btn btn-link text-dark p-0" type="submit" title="Like!">
          <h3 class="far fa-heart status-heart m-0"></h3>
        </button>
      </form>
      <h3 class="far fa-comment pr-3 status-comment-focus" title="Comment"></h3>
      <form class="d-inline-flex share-form pr-3" method="post" action="/i/share" style="display: inline;" data-id="{{$item->id}}" data-action="share" data-count="{{$item->shares_count}}">
        @csrf
        <input type="hidden" name="item" value="{{$item->id}}">
        <button class="btn btn-link text-dark p-0" type="submit" title="Share">
          <h3 class="far fa-share-square m-0"></h3>
        </button>
      </form>
      <span class="float-right">
        <form class="d-inline-flex bookmark-form" method="post" action="/i/bookmark" style="display: inline;" data-id="{{$item->id}}" data-action="bookmark">
          @csrf
          <input type="hidden" name="item" value="{{$item->id}}">
          <button class="btn btn-link text-dark p-0 border-0" type="submit" title="Save">
            <h3 class="far fa-bookmark m-0"></h3>
          </button>
        </form>
      </span>
    </div>
    <div class="likes font-weight-bold">
      <span class="like-count">{{$item->likes_count}}</span> likes
    </div>
    <div class="caption">
      <p class="mb-1">
        <span class="username font-weight-bold">
          <bdi><a class="text-dark" href="{{$item->profile->url()}}">{{$item->profile->username}}</a></bdi>
        </span>
        <span>{!! $item->rendered ?? e($item->caption) !!}</span>
      </p>
    </div>
    <div class="comments">
    </div>
    <div class="timestamp pt-1">
      <p class="small text-uppercase mb-0"><a href="{{$item->url()}}" class="text-muted">{{$item->created_at->diffForHumans()}}</a></p>
    </div>
  </div>
  <div class="card-footer bg-white">
    <form class="comment-form" method="post" action="/i/comment" data-id="{{$item->id}}" data-truncate="true">
      @csrf
      <input type="hidden" name="item" value="{{$item->id}}">
      <input class="form-control status-reply-input" name="comment" placeholder="Add a comment…" autocomplete="off">
    </form>
  </div>
</div>
