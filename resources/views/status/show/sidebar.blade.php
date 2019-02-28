<div class="col-12 col-md-4 px-0 d-flex flex-column border-left border-md-left-0">
  <div class="d-md-flex d-none align-items-center justify-content-between card-header py-3 bg-white">
    <a href="{{$user->url()}}" class="d-flex align-items-center status-username text-truncate" data-toggle="tooltip" data-placement="bottom" title="{{$user->username}}">
      <div class="status-avatar mr-2">
        <img src="{{$user->avatarUrl()}}" width="24px" height="24px" style="border-radius:12px;">
      </div>
      <div class="username">
        <span class="username-link font-weight-bold text-dark">{{$user->username}}</span>
      </div>
    </a>
      <div class="float-right">
        <div class="dropdown">
          <button class="btn btn-link text-dark no-caret dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Post options">
          <span class="fas fa-ellipsis-v text-muted"></span>
          </button>
          <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
            <a class="dropdown-item font-weight-bold" href="{{$status->reportUrl()}}">Report</a>
            {{-- <a class="dropdown-item" href="#">Embed</a> --}}
          @if(Auth::check())
          @if(Auth::user()->profile->id !== $status->profile->id)
          <div class="dropdown-divider"></div>
          <form method="post" action="/i/mute">
            @csrf
            <input type="hidden" name="type" value="user">
            <input type="hidden" name="item" value="{{$status->profile_id}}">
            <button type="submit" class="dropdown-item btn btn-link font-weight-bold">Mute this user</button>
          </form>
          <form method="post" action="/i/block">
            @csrf
            <input type="hidden" name="type" value="user">
            <input type="hidden" name="item" value="{{$status->profile_id}}">
            <button type="submit" class="dropdown-item btn btn-link font-weight-bold">Block this user</button>
          </form>
          @endif
            @if(Auth::user()->profile->id === $status->profile->id || Auth::user()->is_admin == true)
            <div class="dropdown-divider"></div>
            {{-- <a class="dropdown-item" href="{{$status->editUrl()}}">Edit</a> --}}
            <form method="post" action="/i/delete">
              @csrf
              <input type="hidden" name="type" value="post">
              <input type="hidden" name="item" value="{{$status->id}}">
              <button type="submit" class="dropdown-item btn btn-link font-weight-bold">Delete</button>
            </form>
            @endif
          @endif

          </div>
        </div>
      </div>
  </div>
  <div class="d-flex flex-md-column flex-column-reverse h-100">
    <div class="card-body status-comments">
      <div class="status-comment">
        <p class="mb-1">
          <span class="font-weight-bold pr-1">{{$status->profile->username}}</span>
          <span class="comment-text" v-pre>{!! $status->rendered ?? e($status->caption) !!}</span>
        </p>
        <p class="mb-1"><a href="{{$status->url()}}/c" class="text-muted">View all comments</a></p>
        <div class="comments">
          @foreach($replies as $item)
          <p class="mb-1">
            <span class="font-weight-bold pr-1"><bdi><a class="text-dark" href="{{$item->profile->url()}}">{{ str_limit($item->profile->username, 15)}}</a></bdi></span>
            <span class="comment-text" v-pre>{!! $item->rendered ?? e($item->caption) !!} <a href="{{$item->url()}}" class="text-dark small font-weight-bold float-right pl-2">{{$item->created_at->diffForHumans(null, true, true ,true)}}</a></span>
          </p>
          @endforeach
        </div>
      </div>
    </div>
    <div class="card-body flex-grow-0 py-1">
      <div class="reactions my-1">
        @if(Auth::check())
        <form class="d-inline-flex pr-3" method="post" action="/i/like" style="display: inline;" data-id="{{$status->id}}" data-action="like">
          @csrf
          <input type="hidden" name="item" value="{{$status->id}}">
          <button class="btn btn-link text-dark p-0 border-0" type="submit" title="Like!">
            <h3 class="m-0 {{$status->liked() ? 'fas fa-heart text-danger':'far fa-heart text-dark'}}"></h3>
          </button>
        </form>
        <h3 class="far fa-comment pr-3 m-0" title="Comment"></h3>
        <form class="d-inline-flex share-form pr-3" method="post" action="/i/share" style="display: inline;" data-id="{{$status->id}}" data-action="share" data-count="{{$status->shares_count}}">
          @csrf
          <input type="hidden" name="item" value="{{$status->id}}">
          <button class="btn btn-link text-dark p-0" type="submit" title="Share">
            <h3 class="m-0 {{$status->shared() ? 'fas fa-share-square text-primary':'far fa-share-square '}}"></h3>
          </button>
        </form>

        @endif
        <span class="float-right">
          <form class="d-inline-flex " method="post" action="/i/bookmark" style="display: inline;" data-id="{{$status->id}}" data-action="bookmark">
            @csrf
            <input type="hidden" name="item" value="{{$status->id}}">
            <button class="btn btn-link text-dark p-0 border-0" type="submit" title="Save">
              <h3 class="m-0 {{$status->bookmarked() ? 'fas fa-bookmark text-warning':'far fa-bookmark'}}"></h3>
            </button>
          </form>
        </span>
      </div>
      <div class="likes font-weight-bold mb-0">
        <span class="like-count" data-count="{{$status->likes_count}}">{{$status->likes_count}}</span> likes
      </div>
      <div class="timestamp">
        <a href="{{$status->url()}}" class="small text-muted">
          {{$status->created_at->format('F j, Y')}}
        </a>
      </div>
    </div>
  </div>
  <div class="card-footer bg-white sticky-md-bottom">
    <form class="comment-form" method="post" action="/i/comment" data-id="{{$status->id}}" data-truncate="false">
      @csrf
      <input type="hidden" name="item" value="{{$status->id}}">

      <input class="form-control" name="comment" placeholder="Add a comment…" autocomplete="off">
    </form>
  </div>
</div>
