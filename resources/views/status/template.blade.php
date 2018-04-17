      <div class="card my-4">
        <div class="card-header d-inline-flex align-items-center bg-white">
          <img class="img-thumbnail" src="https://placehold.it/32x32" style="border-radius: 32px;">
          <div class="username font-weight-bold pl-2">
            {{$item->profile->username}}
          </div>
          <div class="text-right" style="flex-grow:1;">
            <span class="icon-options"></span>
          </div>
        </div>
        <img class="card-img-top" src="{{$item->mediaUrl()}}">
        <div class="card-body">
          <div class="reactions h3">
            <span class="icon-heart pr-3"></span>
            <span class="icon-speech"></span>
            <span class="float-right">
              <span class="icon-notebook"></span>
            </span>
          </div>
          <div class="likes font-weight-bold">
            0 likes
          </div>
          <div class="caption">
            <p>
              <span class="username font-weight-bold">
                {{$item->profile->username}}
              </span>
              <span>{{$item->caption}}</span>
            </p>
          </div>
          <div class="comments"></div>
          <div class="timestamp">
            <p class="text-muted small text-uppercase mb-0">{{$item->created_at->diffForHumans()}}</p>
          </div>
        </div>
        <div class="card-footer bg-white">
          <form class="comment-form" method="post" action="/i/comment">
            @csrf
            <input type="hidden" name="item" value="{{Hashids::encode($item->id)}}">
            <input class="form-control" placeholder="Add a comment...">
          </form>
        </div>
      </div>