@extends('layouts.app',['title' => $user->username . " posted a photo: " . $status->likes_count . " likes, " . $status->comments_count . " comments" ])

@section('content')

<div class="container px-0 mt-md-4">
  <div class="card card-md-rounded-0 status-container orientation-{{$status->firstMedia()->orientation ?? 'unknown'}}">
    <div class="row mx-0">
    <div class="d-flex d-md-none align-items-center justify-content-between card-header bg-white w-100">
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
      <div class="col-12 col-md-8 status-photo px-0">
        @if($status->is_nsfw && $status->media_count == 1)
        <details class="details-animated">
          <summary>
            <p class="mb-0 lead font-weight-bold">CW / NSFW / Hidden Media</p>
            <p class="font-weight-light">(click to show)</p>
          </summary>
          <a class="max-hide-overflow {{$status->firstMedia()->filter_class}}" href="{{$status->url()}}">
            <img class="card-img-top" src="{{$status->mediaUrl()}}" title="{{$status->firstMedia()->caption}}" data-toggle="tooltip" data-tooltip-placement="bottom">
          </a>
        </details>
        @elseif(!$status->is_nsfw && $status->media_count == 1)
        <div class="{{$status->firstMedia()->filter_class}}">
          <img src="{{$status->mediaUrl()}}" width="100%" title="{{$status->firstMedia()->caption}}" data-toggle="tooltip" data-placement="bottom">
        </div>
        @endif
      </div>
      @include('status.show.sidebar')
    </div>
  </div>
</div>

@endsection

@push('meta')
  <meta property="og:description" content="{{ $status->caption }}">
  <meta property="og:image" content="{{$status->mediaUrl()}}">
  <link href='{{$status->url()}}' rel='alternate' type='application/activity+json'>
@endpush
