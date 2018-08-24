<div class="bg-white py-5 border-bottom">
  <div class="container">
    <div class="row">
      <div class="col-12 col-md-4 d-flex">
        <div class="profile-avatar mx-auto">
          <img class="img-thumbnail" src="{{$user->avatarUrl()}}" style="border-radius:100%;" width="172px">
        </div>
      </div>
      <div class="col-12 col-md-8 d-flex align-items-center">
        <div class="profile-details">
          <div class="username-bar pb-2 d-flex align-items-center">
            <span class="font-weight-ultralight h1">{{$user->username}}</span>
          </div>
          <div class="profile-stats pb-3 d-inline-flex lead">
            <div class="font-weight-light pr-5">
              <span class="font-weight-bold">{{$user->statuses()->whereNull('reblog_of_id')->whereNull('in_reply_to_id')->count()}}</span> 
              Posts
            </div>
          </div>
          <p class="lead mb-0">
            <span class="font-weight-bold">{{$user->name}}</span> 
            @if($user->remote_url)
            <span class="badge badge-info">REMOTE PROFILE</span>
            @endif
          </p>
          <p class="mb-0 lead">{{$user->bio}}</p>
          <p class="mb-0"><a href="{{$user->website}}" class="font-weight-bold" rel="external nofollow noopener" target="_blank">{{str_limit($user->website, 30)}}</a></p>
        </div>
      </div>
    </div>
  </div>
</div>
