@extends('layouts.app',['title' => $profile->username . "’s follows"])

@section('content')

@include('profile.partial.user-info')

<div class="container following-page" style="min-height: 60vh;">
  <div class="col-12 col-md-8 offset-md-2 px-0">
    @if($following->count() !== 0)
    <ul class="list-group mt-4 px-0">
      @foreach($following as $user)
      <li class="list-group-item following card-md-rounded-0">
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

@push('meta')
<meta property="og:description" content="{{$profile->bio}}">
<meta property="og:image" content="{{$profile->avatarUrl()}}">
<meta name="robots" content="NOINDEX, NOFOLLOW">
@endpush
