      <div class="card my-4 status-card">
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
                <a class="dropdown-item" href="{{$item->url()}}">Go to post</a>
                <a class="dropdown-item" href="{{route('report.form')}}?type=post&id={{$item->id}}">Report Inappropriate</a>
                <a class="dropdown-item" href="#">Embed</a>
              @if(Auth::check())
                @if(Auth::user()->profile->id === $item->profile->id || Auth::user()->is_admin == true)
                <a class="dropdown-item" href="{{$item->editUrl()}}">Edit</a>
                <form method="post" action="/i/delete">
                  @csrf
                  <input type="hidden" name="type" value="post">
                  <input type="hidden" name="item" value="{{$item->id}}">
                  <button type="submit" class="dropdown-item btn btn-link">Delete</button>
                </form>
                @endif
              @endif

              </div>
            </div>
          </div>
        </div>
        @if($item->is_nsfw)
        <details class="details-animated">
          <p>
            <summary>NSFW / Hidden Image</summary>
            <a class="max-hide-overflow {{$item->firstMedia()->filter_class}}" href="{{$item->url()}}">
              <img class="card-img-top" src="{{$item->mediaUrl()}}">
            </a>
          </p>
        </details>
        @else
        <a class="max-hide-overflow {{$item->firstMedia()->filter_class}}" href="{{$item->url()}}">
          <img class="card-img-top" src="{{$item->mediaUrl()}}">
        </a>
        @endif
        <div class="card-body">
          <div class="reactions h3">
            <form class="like-form pr-3" method="post" action="/i/like" style="display: inline;" data-id="{{$item->id}}" data-action="like" data-count="{{$item->likes_count}}">
              @csrf
              <input type="hidden" name="item" value="{{$item->id}}">
              <button class="btn btn-link text-dark p-0" type="submit" title=""Like!>
                <span class="far fa-heart status-heart fa-2x"></span>
              </button>
            </form>
            <span class="far fa-comment status-comment-focus" title="Comment"></span>
            <span class="float-right">
              <form class="bookmark-form" method="post" action="/i/bookmark" style="display: inline;" data-id="{{$item->id}}" data-action="bookmark">
                @csrf
                <input type="hidden" name="item" value="{{$item->id}}">
                <button class="btn btn-link text-dark p-0" type="submit" title="Save"><span class="far fa-bookmark" style="font-size:25px;"></span></button>
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
          @if($item->comments()->count() > 3)
          <div class="more-comments">
            <a class="text-muted" href="{{$item->url()}}">Load more comments</a>
          </div>
          @endif
          <div class="comments">
            @if(isset($showSingleComment) && $showSingleComment === true)
              <p class="mb-0">
                <span class="font-weight-bold pr-1">
                  <bdi>
                    <a class="text-dark" href="{{$status->profile->url()}}">{{$status->profile->username}}</a>
                  </bdi>
                </span>
                <span class="comment-text">{!! $item->rendered ?? e($item->caption) !!}</span>
                <span class="float-right">
                  <a href="{{$status->url()}}" class="text-dark small font-weight-bold">
                    {{$status->created_at->diffForHumans(null, true, true, true)}}
                  </a>
                </span>
              </p>
            @else
            @endif
          </div>
          <div class="timestamp pt-1">
            <p class="small text-uppercase mb-0"><a href="{{$item->url()}}" class="text-muted">{{$item->created_at->diffForHumans()}}</a></p>
          </div>
        </div>
        <div class="card-footer bg-white">
          <form class="comment-form" method="post" action="/i/comment" data-id="{{$item->id}}" data-truncate="true">
            @csrf
            <input type="hidden" name="item" value="{{$item->id}}">
            <input class="form-control status-reply-input" name="comment" placeholder="Add a comment…">
          </form>
        </div>
      </div>
