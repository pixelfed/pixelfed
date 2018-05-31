@extends('layouts.app')

@section('content')

<div class="container following-page" style="min-height: 60vh;">

  <div class="profile-header row my-5 offset-md-1">
    <div class="col-12 col-md-3">
      <div class="profile-avatar">
        <img class="img-thumbnail" src="{{$profile->avatarUrl()}}" style="border-radius:100%;" width="172px">
      </div>
    </div>
    <div class="col-12 col-md-9 d-flex align-items-center">
      <div class="profile-details">
        <div class="username-bar pb-2  d-flex align-items-center">
          <span class="font-weight-light h1">{{$profile->username}}</span>
        </div>
        <div class="profile-stats pb-3 d-inline-flex lead">
          <div class="font-weight-light pr-5">
            <a class="text-dark" href="{{$profile->url()}}">
              <span class="font-weight-bold">{{$profile->statuses()->count()}}</span> 
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

  <div class="col-12 col-md-8 offset-2">
    @if($following->count() !== 0)
    <ul class="list-group mt-4">
      @foreach($following as $user)
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
      </li>
      @endforeach
    </ul>
    @else
      <div class="col-12">
        <div class="card">
          <div class="card-body py-5 my-5">
            <div class="d-flex my-5 py-5 justify-content-center align-items-center">
              <p class="lead font-weight-bold">{{ __('profile.emptyFollowing') }}</p>
            </div>
          </div>
        </div>
      </div>
    @endif
    <div class="d-flex justify-content-center mt-4">
      {{$following->links()}}
    </div>
  </div>
</div>
@endsection