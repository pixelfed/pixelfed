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
        @if($status->is_nsfw)
        <details class="details-animated">
          <summary>
            <p class="mb-0 lead font-weight-bold">CW / NSFW / Hidden Media</p>
            <p class="font-weight-light">(click to show)</p>
          </summary>
          @endif
            <div id="photoCarousel" class="carousel slide carousel-fade" data-ride="carousel">
              <ol class="carousel-indicators">
                @for($i = 0; $i < $status->media_count; $i++)
                <li data-target="#photoCarousel" data-slide-to="{{$i}}" class="{{$i == 0 ? 'active' : ''}}"></li>
                @endfor
              </ol>
              <div class="carousel-inner">
                @foreach($status->media()->orderBy('order')->get() as $media)
                <div class="carousel-item {{$loop->iteration == 1 ? 'active' : ''}}">
                  <figure class="{{$media->filter_class}}">
                    <img class="d-block w-100" src="{{$media->url()}}" title="{{$media->caption}}" data-toggle="tooltip" data-placement="bottom">
                  </figure>
                </div>
                @endforeach
              </div>
              <a class="carousel-control-prev" href="#photoCarousel" role="button" data-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="sr-only">Previous</span>
              </a>
              <a class="carousel-control-next" href="#photoCarousel" role="button" data-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="sr-only">Next</span>
              </a>
            </div>
        @if($status->is_nsfw)
          </details>
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
