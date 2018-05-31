@extends('layouts.app')

@section('content')

<div class="container">
  
  <div class="profile-header row my-5 offset-md-1">
    <div class="col-12 col-md-3">
      <div class="profile-avatar">
        <img class="img-thumbnail" src="{{$user->avatarUrl()}}" style="border-radius:100%;" width="172px">
      </div>
    </div>
    <div class="col-12 col-md-9 d-flex align-items-center">
      <div class="profile-details">
        <div class="username-bar pb-2  d-flex align-items-center">
          <span class="font-weight-light h1">{{$user->username}}</span>
          @if($owner == true)
          <span class="pl-4">
            <a class="btn btn-outline-secondary font-weight-bold px-4 py-0" href="{{route('settings')}}">Settings</a>
          </span>
          @elseif ($following == true)
          <span class="pl-4">
            <form class="follow-form" method="post" action="/i/follow" style="display: inline;" data-id="{{$user->id}}" data-action="unfollow">
              @csrf
              <input type="hidden" name="item" value="{{$user->id}}">
              <button class="btn btn-outline-secondary font-weight-bold px-4 py-0" type="submit">Unfollow</button>
            </form>
          </span>
          @elseif ($following == false)
          <span class="pl-4">
            <form class="follow-form" method="post" action="/i/follow" style="display: inline;" data-id="{{$user->id}}" data-action="follow">
              @csrf
              <input type="hidden" name="item" value="{{$user->id}}">
              <button class="btn btn-primary font-weight-bold px-4 py-0" type="submit">Follow</button>
            </form>
          </span>
          @endif
          {{-- TODO: Implement action dropdown
          <span class="pl-4">
            <div class="dropdown">
              <button class="btn btn-secondary dropdown-toggle py-0" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="icon-options"></i>
              </button>
              <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <a class="dropdown-item" href="#">Report User</a>
                <a class="dropdown-item" href="#">Block User</a>
              </div>
            </div>
          </span>--}}
        </div>
        <div class="profile-stats pb-3 d-inline-flex lead">
          <div class="font-weight-light pr-5">
            <span class="font-weight-bold">{{$user->statuses()->count()}}</span> 
            Posts
          </div>
          <div class="font-weight-light pr-5">
            <a class="text-dark" href="{{$user->url('/followers')}}">
              <span class="font-weight-bold">{{$user->followerCount(true)}}</span> 
              Followers
            </a>
          </div>
          <div class="font-weight-light pr-5">
            <a class="text-dark" href="{{$user->url('/following')}}">
              <span class="font-weight-bold">{{$user->followingCount(true)}}</span> 
              Following
            </a>
          </div>
        </div>
        <p class="lead">
          <span class="font-weight-bold">{{$user->name}}</span> 
          @if($user->remote_url)
          <span class="badge badge-info">REMOTE PROFILE</span>
          @endif
          {{$user->bio}}
        </p>
      </div>
    </div>
  </div>

  <div class="profile-timeline mt-5 row">
    @if($owner == true)
      <div class="col-12 mb-5">
        <ul class="nav nav-tabs d-flex justify-content-center">
          <li class="nav-item mr-3">
            <a class="nav-link {{request()->is('*/saved') ? '':'active'}} font-weight-bold text-uppercase" href="{{$user->url()}}">Posts</a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{request()->is('*/saved') ? 'active':''}} font-weight-bold text-uppercase" href="{{$user->url('/saved')}}">Saved</a>
          </li>
        </ul>
      </div>
    @endif

    @if($owner && request()->is('*/saved'))
    <div class="col-12">
      <p class="text-muted font-weight-bold small">{{__('profile.savedWarning')}}</p>
    </div>
    @endif

    @if($timeline->count() > 0)
      @foreach($timeline as $status)
      <div class="col-12 col-md-4 mb-4">
        <a class="card" href="{{$status->url()}}">
          <img class="card-img-top" src="{{$status->thumb()}}" width="300px" height="300px">
        </a>
      </div>
      @endforeach
    @else
      <div class="col-12">
        <div class="card">
          <div class="card-body py-5 my-5">
            <div class="d-flex my-5 py-5 justify-content-center align-items-center">
              <p class="lead font-weight-bold">{{ __('profile.emptyTimeline') }}</p>
            </div>
          </div>
        </div>
      </div>
    @endif
  </div>

</div>

@endsection