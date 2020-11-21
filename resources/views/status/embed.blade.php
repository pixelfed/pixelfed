<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="mobile-web-app-capable" content="yes">

    <title>{{ $title ?? config('app.name', 'Pixelfed') }}</title>

    <meta property="og:site_name" content="{{ config('app.name', 'pixelfed') }}">
    <meta property="og:title" content="{{ $title ?? config('app.name', 'pixelfed') }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{$status->url()}}">
    <meta name="medium" content="image">
    <meta name="theme-color" content="#10c5f8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="shortcut icon" type="image/png" href="/img/favicon.png?v=2">
    <link rel="apple-touch-icon" type="image/png" href="/img/favicon.png?v=2">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <style type="text/css">
      body.embed-card {
          background: #fff !important;
          margin: 0;
          padding-bottom: 0;
      }
      .status-card-embed {
        box-shadow: none;
        border-radius: 4px;
        overflow: hidden;
      }
    </style>
</head>
<body class="bg-white">
  <div class="embed-card">
  @php($item = $status)
  <div class="card status-card-embed card-md-rounded-0 border">
    <div class="card-header d-inline-flex align-items-center bg-white">
      <img src="{{$item->profile->avatarUrl()}}" width="32px" height="32px" target="_blank" style="border-radius: 32px;">
      <a class="username font-weight-bold pl-2 text-dark" target="_blank" href="{{$item->profile->url()}}">
        {{$item->profile->username}}
      </a>
    </div>
    <a href="{{$status->url()}}" target="_blank">
    @php($status = $item)
    @switch($status->viewType())
      @case('photo')
      @case('image')
        @if($status->is_nsfw)
        <details class="details-animated">
          <summary>
            <p class="mb-0 lead font-weight-bold">CW / NSFW / Hidden Media</p>
            <p class="font-weight-light">(click to show)</p>
          </summary>
          <a class="max-hide-overflow {{$status->firstMedia()->filter_class}}" href="{{$status->url()}}" target="_blank">
            <img class="card-img-top" src="{{$status->mediaUrl()}}">
          </a>
        </details>
        @else
        <div class="{{$status->firstMedia()->filter_class}}">
          <img src="{{$status->mediaUrl()}}" width="100%">
        </div>
        @endif
      @break
      @case('photo:album')
        <div id="photo-carousel-wrapper-{{$status->id}}" class="carousel slide carousel-fade mb-n3 " data-ride="carousel">
          <ol class="carousel-indicators">
            @for($i = 0; $i < $status->media_count; $i++)
            <li data-target="#photo-carousel-wrapper-{{$status->id}}" data-slide-to="{{$i}}" class="{{$i == 0 ? 'active' : ''}}"></li>
            @endfor
          </ol>
          <div class="carousel-inner">
            @foreach($status->media()->orderBy('order')->get() as $media)
            <div class="carousel-item {{$loop->iteration == 1 ? 'active' : ''}}">
              <figure class="{{$media->filter_class}}">
                <div class="float-right mr-3 badge badge-dark border border-secondary rounded-pill p-2" style="position:absolute;top:8px;right:0;margin-bottom:-20px;">{{$loop->iteration}}/{{$loop->count}}</div>
                <img class="d-block w-100" src="{{$media->url()}}" alt="{{$status->caption}}">
              </figure>
            </div>
            @endforeach
          </div>
          <a class="carousel-control-prev" href="#photo-carousel-wrapper-{{$status->id}}" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
          </a>
          <a class="carousel-control-next" href="#photo-carousel-wrapper-{{$status->id}}" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
          </a>
        </div>
      @break
      @case('video')
        @if($status->is_nsfw)
        <details class="details-animated">
          <summary>
            <p class="mb-0 lead font-weight-bold">CW / NSFW / Hidden Media</p>
            <p class="font-weight-light">(click to show)</p>
          </summary>
          <div class="embed-responsive embed-responsive-16by9">
            <video class="video" preload="none" controls loop>
              <source src="{{$status->firstMedia()->url()}}" type="{{$status->firstMedia()->mime}}">
            </video>
          </div>
         </details>
        @else
        <div class="embed-responsive embed-responsive-16by9">
          <video class="video" preload="none" controls loop>
            <source src="{{$status->firstMedia()->url()}}" type="{{$status->firstMedia()->mime}}">
          </video>
        </div>
        @endif
      @break
      @case('video-album')
        @if($status->is_nsfw)
        <details class="details-animated">
          <summary>
            <p class="mb-0 lead font-weight-bold">CW / NSFW / Hidden Media</p>
            <p class="font-weight-light">(click to show)</p>
          </summary>
          <div class="embed-responsive embed-responsive-16by9">
            <video class="video" preload="none" controls loop>
              <source src="{{$status->firstMedia()->url()}}" type="{{$status->firstMedia()->mime}}">
            </video>
          </div>
         </details>
        @else
        <div class="embed-responsive embed-responsive-16by9">
          <video class="video" preload="none" controls loop>
            <source src="{{$status->firstMedia()->url()}}" type="{{$status->firstMedia()->mime}}">
          </video>
        </div>
        @endif
      @break
    @endswitch
  </a>
  @if($layout != 'compact')
    <div class="card-body">
      <div class="view-more mb-2">
        <a class="font-weight-bold" href="{{$status->url()}}" target="_blank">View More on Pixelfed</a>
      </div>
      <hr>
      @if($showLikes)
      <div class="likes font-weight-bold pb-2">
        <span class="like-count">{{$item->likes_count}}</span> likes
      </div>
      @endif
      <div class="caption">
        <p class="my-0">
          <span class="username font-weight-bold">
            <bdi><a class="text-dark" href="{{$item->profile->url()}}" target="_blank">{{$item->profile->username}}</a></bdi>
          </span>
         @if($showCaption)
          <span class="caption-container">{!! $item->rendered ?? e($item->caption) !!}</span>
          @endif
        </p>
      </div>
    </div>
    @endif
    <div class="card-footer bg-white d-inline-flex justify-content-between align-items-center">
      <div class="timestamp">
        <p class="small text-uppercase mb-0"><a href="{{$item->url()}}" class="text-muted" target="_blank">{{$item->created_at->diffForHumans()}}</a></p>
      </div>
      <div>
        <a class="small font-weight-bold text-muted pr-1" href="{{config('app.url')}}" target="_blank">{{config('pixelfed.domain.app')}}</a>
        <img src="/img/pixelfed-icon-color.svg" width="26px">
      </div>
    </div>
  </div>
  </div>
  <script type="text/javascript">window.addEventListener("message",e=>{const t=e.data||{};window.parent&&"setHeight"===t.type&&window.parent.postMessage({type:"setHeight",id:t.id,height:document.getElementsByTagName("html")[0].scrollHeight},"*")});</script>
  <script type="text/javascript">document.querySelectorAll('.caption-container a').forEach(function(i) {i.setAttribute('target', '_blank');});</script>
    <script type="text/javascript" src="{{ mix('js/manifest.js') }}"></script>
    <script type="text/javascript" src="{{ mix('js/vendor.js') }}"></script>
    <script type="text/javascript" src="{{ mix('js/app.js') }}"></script>
</body>
</html>
