      <div class="card my-4 status-card">
        <div class="card-header d-inline-flex align-items-center bg-white">
          <img class="img-thumbnail" src="{{$item->profile->avatarUrl()}}" width="32px" height="32px" style="border-radius: 32px;">
          <a class="username font-weight-bold pl-2 text-dark" href="{{$item->profile->url()}}">
            {{$item->profile->username}}
          </a>
          <div class="text-right" style="flex-grow:1;">
            <div class="dropdown">
              <button class="btn btn-link text-dark no-caret dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <span class="icon-options"></span>
              </button>
              <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                <a class="dropdown-item" href="{{$item->url()}}">Go to post</a>
                <a class="dropdown-item" href="{{route('report.form')}}?type=post&id={{$item->id}}">Report Inappropriate</a>
                <a class="dropdown-item" href="#">Embed</a>
              </div>
            </div>
          </div>
        </div>
        <a href="{{$item->url()}}">
          <img class="card-img-top" src="{{$item->mediaUrl()}}">
        </a>
        <div class="card-body">
          <div class="reactions h3">
            <form class="like-form pr-3" method="post" action="/i/like" style="display: inline;" data-id="{{$item->id}}" data-action="like">
              @csrf
              <input type="hidden" name="item" value="{{$item->id}}">
              <button class="btn btn-link text-dark p-0" type="submit"><span class="icon-heart" style="font-size:25px;"></span></button>
            </form>
            <span class="icon-speech"></span>
            <span class="float-right">
              <form class="bookmark-form" method="post" action="/i/bookmark" style="display: inline;" data-id="{{$item->id}}" data-action="bookmark">
                @csrf
                <input type="hidden" name="item" value="{{$item->id}}">
                <button class="btn btn-link text-dark p-0" type="submit"><span class="icon-notebook" style="font-size:25px;"></span></button>
              </form>
            </span>
          </div>
          <div class="likes font-weight-bold">
            <span class="like-count">{{$item->likes()->count()}}</span> likes
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
            <a class="text-muted" href="#">Load more comments</a>
          </div>
          @endif
          <div class="comments">
            @if(isset($showSingleComment) && $showSingleComment === true)
              <p class="mb-0">
                <span class="font-weight-bold pr-1"><bdi><a class="text-dark" href="{{$status->profile->url()}}">{{$status->profile->username}}</a></bdi></span>
                <span class="comment-text">{!!$status->rendered!!}</span><a href="{{$status->url()}}" class="text-dark small font-weight-bold float-right">{{$status->created_at->diffForHumans(null, true, true, true)}}</a>
              </p>
            @else
            @foreach($item->comments->reverse()->take(3) as $comment)
              <p class="mb-0">
                <span class="font-weight-bold pr-1"><bdi><a class="text-dark" href="{{$comment->profile->url()}}">{{$comment->profile->username}}</a></bdi></span>
                <span class="comment-text">{{ str_limit($comment->caption, 125) }}</span>
              </p>
            @endforeach
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
            <input class="form-control" name="comment" placeholder="Add a comment...">
          </form>
        </div>
      </div>