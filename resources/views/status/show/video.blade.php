@extends('layouts.app',['title' => $user->username . " posted a photo: " . $status->likes_count . " likes, " . $status->comments_count . " comments" ])

@section('content')

<div class="container px-0 mt-md-4">
  <div class="card card-md-rounded-0 status-container orientation-video">
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
     </div>
      <div class="col-12 col-md-8 status-photo px-0">
        @if($status->is_nsfw && $status->media_count == 1)
        <details class="details-animated">
          <summary>
            <p class="mb-0 lead font-weight-bold">CW / NSFW / Hidden Media</p>
            <p class="font-weight-light">(click to show)</p>
          </summary>
          <div class="embed-responsive embed-responsive-16by9">
            <video class="embed-responsive-item" controls="">
              <source src="{{$status->mediaUrl()}}" type="video/mp4">
            </video>
          </div>
        </details>
        @elseif(!$status->is_nsfw && $status->media_count == 1)
        <div class="embed-responsive embed-responsive-16by9">
          <video class="embed-responsive-item" controls="">
            <source src="{{$status->mediaUrl()}}" type="video/mp4">
          </video>
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
