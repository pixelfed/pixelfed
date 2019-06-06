@extends('layouts.app',['title' => $user->username . " on " . config('app.name')])

@section('content')
@if (session('error'))
    <div class="alert alert-danger text-center font-weight-bold mb-0">
        {{ session('error') }}
    </div>
@endif
@include('profile.partial.user-info')

@if(true === $owner)
<div>
  <ul class="nav nav-topbar d-flex justify-content-center border-0">
    <li class="nav-item">
      <a class="nav-link {{request()->is($user->username) ? 'active': ''}} font-weight-bold text-uppercase" href="{{$user->url()}}">Posts</a>
    </li>
    {{-- <li class="nav-item">
      <a class="nav-link {{request()->is('*/collections') ? 'active': ''}} font-weight-bold text-uppercase" href="{{$user->url()}}/collections">Collections</a>
    </li> --}}
    <li class="nav-item">
      <a class="nav-link {{request()->is('*/saved') ? 'active':''}} font-weight-bold text-uppercase" href="{{$user->url('/saved')}}">Saved</a>
    </li>
  </ul>
</div>
@endif
<div class="container">
    @if($owner && request()->is('*/saved'))
    <div class="col-12">
      <p class="text-muted font-weight-bold small">{{__('profile.savedWarning')}}</p>
    </div>
    @endif
  <div class="profile-timeline mt-2 mt-md-4">
    <div class="row">
    @if($timeline->count() > 0)
    @foreach($timeline as $status)
      <div class="col-4 p-0 p-sm-2 p-md-3">
        <a class="card info-overlay card-md-border-0" href="{{$status->url()}}">
          <div class="square {{$status->firstMedia()->filter_class}}">
            @switch($status->viewType())
            @case('album')
            @case('photo:album')
            <span class="float-right mr-3" style="color:#fff;position:relative;margin-top:10px;z-index: 999999;opacity:0.6;text-shadow: 3px 3px 16px #272634;"><i class="fas fa-images fa-2x"></i></span>
            @break
            @case('video')
            <span class="float-right mr-3" style="color:#fff;position:relative;margin-top:10px;z-index: 999999;opacity:0.6;text-shadow: 3px 3px 16px #272634;"><i class="fas fa-video fa-2x"></i></span>
            @break
            @case('video-album')
            <span class="float-right mr-3" style="color:#fff;position:relative;margin-top:10px;z-index: 999999;opacity:0.6;text-shadow: 3px 3px 16px #272634;"><i class="fas fa-film fa-2x"></i></span>
            @break
            @endswitch
            <div class="square-content" style="background-image: url('{{$status->thumb()}}')">
            </div>
            <div class="info-overlay-text">
              <h5 class="text-white m-auto font-weight-bold">
                <span>
                  <span class="far fa-heart fa-lg p-2 d-flex-inline"></span>
                  <span class="d-flex-inline">{{App\Util\Lexer\PrettyNumber::convert($status->likes_count)}}</span>
                </span>
                <span>
                  <span class="far fa-comment fa-lg p-2 d-flex-inline"></span>
                  <span class="d-flex-inline">{{App\Util\Lexer\PrettyNumber::convert($status->comments_count)}}</span>
                </span>
              </h5>
            </div>
          </div>
        </a>
      </div>
    @endforeach
    </div>
  </div>
  <div class="pagination-container">
    <div class="d-flex justify-content-center">
      {{$timeline->links()}}
    </div>
  </div>
    @else
      <div class="col-12">
        <div class="card">
          <div class="card-body py-5 my-5">
            <div class="d-flex my-5 py-5 justify-content-center align-items-center">
              @if($owner && request()->is('*/saved'))
                <p class="lead font-weight-bold">{{ __('profile.emptySaved') }}</p>
              @else
                <p class="lead font-weight-bold">{{ __('profile.emptyTimeline') }}</p>
              @endif
            </div>
          </div>
      </div>
    </div>
  </div>
    @endif

</div>

@endsection

@push('meta')<meta property="og:description" content="{{$user->bio}}">
    <meta property="og:image" content="{{$user->avatarUrl()}}">
    <link href="{{$user->permalink('.atom')}}" rel="alternate" title="{{$user->username}} on Pixelfed" type="application/atom+xml">
  @if(false == $settings->crawlable || $user->remote_url)
  <meta name="robots" content="noindex, nofollow">
  @endif
@endpush

@push('scripts')
<script type="text/javascript">
  $(document).ready(function() {
    $('.pagination-container').hide();
    $('.pagination').hide();
    let elem = document.querySelector('.profile-timeline');
    let infScroll = new InfiniteScroll( elem, {
      path: '.pagination__next',
      append: '.profile-timeline .row',
      status: '.page-load-status',
      history: false,
    });
  });
</script>

@endpush

