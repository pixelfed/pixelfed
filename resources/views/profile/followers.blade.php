@extends('layouts.app',['title' => $user->username . "'s followers"])

@section('content')

<div class="container following-page" style="min-height: 60vh;">

  <div class="profile-header row my-5">
    <div class="col-12 col-md-4 d-flex">
      <div class="profile-avatar mx-auto">
        <img class="img-thumbnail" src="{{$profile->avatarUrl()}}" style="border-radius:100%;" width="172px">
      </div>
    </div>
    <div class="col-12 col-md-8 d-flex align-items-center">
      <div class="profile-details">
        <div class="username-bar pb-2 d-flex align-items-center">
          <span class="font-weight-ultralight h1">{{$profile->username}}</span>
        </div>
        <div class="profile-stats pb-3 d-inline-flex lead">
          <div class="font-weight-light pr-5">
            <a class="text-dark" href="{{$profile->url()}}">
              <span class="font-weight-bold">{{$profile->statuses()->whereNull('in_reply_to_id')->count()}}</span> 
              Posts
            </a>
          </div>
          <div class="font-weight-light pr-5">
            <a class="text-dark" href="{{$profile->url('/followers')}}">
              <span class="font-weight-bold">{{$profile->followerCount(true)}}</span> 
              Followers
            </a>
          </div>
          <div class="font-weight-light pr-5">
            <a class="text-dark" href="{{$profile->url('/following')}}">
              <span class="font-weight-bold">{{$profile->followingCount(true)}}</span> 
              Following
            </a>
          </div>
        </div>
        <p class="lead font-weight-bold">
          {{$profile->name}}
        </p>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-8 offset-md-2">
    @if($followers->count() !== 0)
    <ul class="list-group mt-4">
      @foreach($followers as $user)
      <li class="list-group-item following">
          <span class="following-icon pr-3">
            <img src="{{$user->avatarUrl()}}" width="32px" class="rounded-circle">
          </span>
          <a class="following-username font-weight-bold text-dark" href="{{$user->url()}}">
            {{$user->username}}
          </a>
          <span class="following-name text-muted">
            {{$user->name}}
          </span>
          @if(Auth::check() && Auth::id() != $user->user_id)
            @if ($user->followedBy(Auth::user()->profile) == true)
            <span class="float-right notification-action">
              <form class="follow-form" method="post" action="/i/follow" style="display: inline;" data-id="{{$user->id}}" data-action="unfollow">
                @csrf
                <input type="hidden" name="item" value="{{$user->id}}">
                <button class="btn btn-outline-secondary font-weight-bold px-4 py-0" type="submit">Unfollow</button>
              </form>
            </span>
            @else
            <span class="float-right notification-action">
              <form class="follow-form" method="post" action="/i/follow" style="display: inline;" data-id="{{$user->id}}" data-action="follow">
                @csrf
                <input type="hidden" name="item" value="{{$user->id}}">
                <button class="btn btn-primary font-weight-bold px-4 py-0" type="submit">Follow</button>
              </form>
            </span>
            @endif
          @endif
      </li>
      @endforeach
    </ul>
    @else
      <div class="col-12">
        <div class="card">
          <div class="card-body py-5 my-5">
            <div class="d-flex my-5 py-5 justify-content-center align-items-center">
              <p class="lead font-weight-bold">{{ __('profile.emptyFollowers') }}</p>
            </div>
          </div>
        </div>
      </div>
    @endif
    <div class="d-flex justify-content-center mt-4">
      {{$followers->links()}}
    </div>
  </div>
</div>
@endsection

@push('meta')
<meta property="og:description" content="{{$user->bio}}">
<meta property="og:image" content="{{$user->avatarUrl()}}">
@endpush
