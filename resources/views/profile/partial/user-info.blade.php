<div class="bg-white py-5 border-bottom">
  <div class="container">
    <div class="row">
      <div class="col-12 col-md-4 d-flex">
        <div class="profile-avatar mx-auto">
          <img class="rounded-circle box-shadow" src="{{$user->avatarUrl()}}" width="172px" height="172px">
        </div>
      </div>
      <div class="col-12 col-md-8 d-flex align-items-center">
        <div class="profile-details">
          <div class="username-bar pb-2 d-flex align-items-center">
            <span class="font-weight-ultralight h1">{{$user->username}}</span>
            @if($is_admin == true)
            <span class="pl-4">
              <span class="btn btn-outline-danger font-weight-bold py-0">ADMIN</span>
            </span>
            @endif
            @if($owner == true)
            <span class="pl-4">
            <a class="fas fa-cog fa-lg text-muted" href="{{route('settings')}}"></a>
            </span>
            @elseif (Auth::check() && $is_following == true)
            <span class="pl-4">
              <form class="follow-form" method="post" action="/i/follow" style="display: inline;" data-id="{{$user->id}}" data-action="unfollow">
                @csrf
                <input type="hidden" name="item" value="{{$user->id}}">
                <button class="btn btn-outline-secondary font-weight-bold px-4 py-0" type="submit">Unfollow</button>
              </form>
            </span>
            @elseif (Auth::check() && $is_following == false)
            <span class="pl-4">
              <form class="follow-form" method="post" action="/i/follow" style="display: inline;" data-id="{{$user->id}}" data-action="follow">
                @csrf
                <input type="hidden" name="item" value="{{$user->id}}">
                <button class="btn btn-primary font-weight-bold px-4 py-0" type="submit">Follow</button>
              </form>
            </span>
            @endif
            {{-- <span class="pl-4">
              <div class="dropdown">
                <button class="btn btn-link text-muted dropdown-toggle py-0" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="text-decoration: none;">
                <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                  <a class="dropdown-item font-weight-bold" href="#">Report User</a>
                  <div class="dropdown-divider"></div>
                  <a class="dropdown-item font-weight-bold" href="#">Mute User</a>
                  <a class="dropdown-item font-weight-bold" href="#">Block User</a>
                  <a class="dropdown-item font-weight-bold mute-users" href="#">Mute User & User Followers</a>
                  <a class="dropdown-item font-weight-bold" href="#">Block User & User Followers</a>
                </div>
              </div>
            </span>
           --}}
          </div>
          <div class="profile-stats pb-3 d-inline-flex lead">
            <div class="font-weight-light pr-5">
              <a class="text-dark" href="{{$user->url()}}">
              <span class="font-weight-bold">{{$user->statusCount()}}</span> 
              Posts
              </a>
            </div>
            @if($settings->show_profile_follower_count)
            <div class="font-weight-light pr-5">
              <a class="text-dark" href="{{$user->url('/followers')}}">
              <span class="font-weight-bold">{{$user->followerCount(true)}}</span> 
              Followers
              </a>
            </div>
            @endif
            @if($settings->show_profile_following_count)
            <div class="font-weight-light pr-5">
              <a class="text-dark" href="{{$user->url('/following')}}">
              <span class="font-weight-bold">{{$user->followingCount(true)}}</span> 
              Following
              </a>
            </div>
            @endif
          </div>
          <p class="lead mb-0 d-flex align-items-center">
            <span class="font-weight-bold pr-3">{{$user->name}}</span> 
            @if($user->remote_url)
            <span class="btn btn-outline-secondary btn-sm py-0">REMOTE PROFILE</span>
            @endif
          </p>
          <div class="mb-0 lead" v-pre>{!!str_limit($user->bio, 127)!!}</div>
          <p class="mb-0"><a href="{{$user->website}}" class="font-weight-bold" rel="me external nofollow noopener" target="_blank">{{str_limit($user->website, 30)}}</a></p>
        </div>
      </div>
    </div>
  </div>
</div>
