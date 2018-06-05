@extends('layouts.app',['title' => $user->username . " on " . config('app.name')])

@section('content')

@include('profile.partial.user-info')

@if($owner == true)
<div>
  <ul class="nav nav-topbar d-flex justify-content-center">
    <li class="nav-item">
      <a class="nav-link {{request()->is('*/saved') ? '':'active'}} font-weight-bold text-uppercase" href="{{$user->url()}}">Posts</a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{request()->is('*/saved') ? 'active':''}} font-weight-bold text-uppercase" href="{{$user->url('/saved')}}">Saved</a>
    </li>
  </ul>
</div>
@endif
<div class="container">
  <div class="profile-timeline mt-5 row">
    @if($owner && request()->is('*/saved'))
    <div class="col-12">
      <p class="text-muted font-weight-bold small">{{__('profile.savedWarning')}}</p>
    </div>
    @endif
    @if($timeline->count() > 0)
    @foreach($timeline as $status)
    <div class="col-12 col-md-4 mb-4">
      <a class="card info-overlay" href="{{$status->url()}}">
        <div class="square">
          <div class="square-content" style="background-image: url('{{$status->thumb()}}')"></div>
          <div class="info-overlay-text">
            <h5 class="text-white m-auto font-weight-bold">
              <span class="pr-4">
              <span class="icon-heart pr-1"></span> {{$status->likes_count}}
              </span>
              <span>
              <span class="icon-speech pr-1"></span> {{$status->comments_count}}
              </span>
            </h5>
          </div>
        </div>
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

@push('meta')
<meta property="og:description" content="{{$user->bio}}">
<meta property="og:image" content="{{$user->avatarUrl()}}">
@endpush

