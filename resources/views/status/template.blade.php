<div class="card mb-4 status-card card-md-rounded-0" data-id="{{$item->id}}" data-comment-max-id="0" data-profile-username="{{$item->profile->username}}" data-profile-name="{{$item->profile->name}}" data-timestamp="{{$item->created_at}}">
  <div class="card-header d-inline-flex align-items-center bg-white">
    <img src="{{$item->profile->avatarUrl()}}" width="32" height="32" style="border-radius: 32px;">
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
          {{-- <a class="dropdown-item font-weight-bold" href="#" onclick="pixelfed.embed.onclick(this)">Embed</a> --}}
        @if(Auth::check())
          @if(Auth::user()->profile->id !== $item->profile->id)
          <div class="dropdown-divider"></div>
          <form method="post" action="/i/mute">
            @csrf
            <input type="hidden" name="type" value="user">
            <input type="hidden" name="item" value="{{$item->profile_id}}">
            <button type="submit" class="dropdown-item btn btn-link font-weight-bold">Mute this user</button>
          </form>
          <form method="post" action="/i/block">
            @csrf
            <input type="hidden" name="type" value="user">
            <input type="hidden" name="item" value="{{$item->profile_id}}">
            <button type="submit" class="dropdown-item btn btn-link font-weight-bold">Block this user</button>
          </form>
          @endif
          @if(Auth::user()->profile->id === $item->profile->id || Auth::user()->is_admin == true)
          <div class="dropdown-divider"></div>
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
  @php($status = $item)
  @switch($status->viewType())
    @case('image')
      @include('status.timeline.photo')
    @break
    @case('album')
      @include('status.timeline.album')
    @break
    @case('video')
      @include('status.timeline.video')
    @break
    @case('video-album')
      @include('status.timeline.video-album')
    @break
  @endswitch
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
      <p class="mb-1 read-more" style="overflow: hidden;">
        <span class="username font-weight-bold">
          <bdi><a class="text-dark" href="{{$item->profile->url()}}" v-pre>{{$item->profile->username}}</a></bdi>
        </span>
        <span v-pre>{!! $item->rendered ?? e($item->caption) !!}</span>
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
