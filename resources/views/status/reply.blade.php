@extends('layouts.app')

@section('content')

<div class="container reply-container">
  <div class="col-12 col-md-8 offset-md-2 mt-4">
      <div class="card">
        <div class="card-body p-0 m-0 bg-light">
          <div class="d-flex p-0 m-0 align-items-center">
            <img src="{{$status->parent()->thumb()}}" width="150px" height="150px" class="post-thumbnail">
            <div class="p-4 w-100">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <img src="{{$status->parent()->profile->avatarUrl()}}" class="rounded-circle img-thumbnail mb-1 mr-1" width="30px">
                  <span class="h5 font-weight-bold" v-pre>{{$status->parent()->profile->username}}</span>
                </div>
                <div>
                  <a class="" href="{{$status->parent()->url()}}"><i class="far fa-share-square"></i></a>
                </div>
              </div>
              <div class="">
                <p class="mb-0 w-100" v-pre>{{ str_limit($status->parent()->caption, 125) }}</p>
              </div>
            </div>
          </div>
        </div>
        <div class="card-body">
          @if($status->is_nsfw)
          <details class="cw">
            <summary class="px-3 px-md-5">
              <p class="py-5 mb-0 text-center">This comment may contain sensitive content. <span class="float-right font-weight-bold text-primary">Show</span></p>
            </summary>
            <div class="media py-5">
              <img class="mr-3 rounded-circle img-thumbnail" src="{{$status->profile->avatarUrl()}}" width="60px">
              <div class="media-body">
                <h5 class="mt-0 font-weight-bold" v-pre>{{$status->profile->username}}</h5>
                <p class="mb-1" v-pre>{!! $status->rendered !!}</p>
                <div class="mb-0">
                  <a href="{{$status->url()}}" class="text-muted">
                    {{$status->created_at->diffForHumans()}}
                  </a>
                </div>
              </div>
            </div>
          </details>
          @else
          <div class="media py-5">
            <img class="mr-3 rounded-circle img-thumbnail" src="{{$status->profile->avatarUrl()}}" width="60px">
            <div class="media-body">
              <h5 class="mt-0 font-weight-bold" v-pre>{{$status->profile->username}}</h5>
              <p class="mb-1" v-pre>{!! $status->rendered !!}</p>
              <div class="mb-0">
                <a href="{{$status->url()}}" class="text-muted">
                  {{$status->created_at->diffForHumans()}}
                </a>
                @if(Auth::check() && $status->profile_id == Auth::user()->profile->id)
                <form class="float-right" method="POST" action="/i/delete">
                  @csrf
                  <input type="hidden" name="item" value="{{$status->id}}">
                  <input type="hidden" name="type" value="status">
                  <button class="btn btn-outline-danger small font-weight-bold btn-sm py-1">Delete</button>
                </form>
                @endif
              </div>
            </div>
          </div>
          @endif
        </div>
      </div>
  </div>
</div>

@endsection

@push('styles')
<style type="text/css">
  @keyframes fadeInDown {
    0% {
      opacity: 0;
      transform: translateY(-1.25em);
    }
    100% {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .cw[open] {
    animation-name: fadeInDown;
    animation-duration: 0.5s;
  }

  .cw>summary {
      display: flex;
      flex-flow: column;
      justify-content: center;
      border: 0;
      background-color: #fff;
      padding-top: 50px;
      padding-bottom: 50px;
      text-align: center;
  }

  .cw[open] > summary {
    display: none!important;
  }
</style>
@endpush

@push('scripts')
<script type="text/javascript" src="{{ mix('js/compose.js') }}"></script>
<script type="text/javascript">
  $(document).ready(function() {
    $('.reactions').hide();
    $('.more-comments').hide();
    $('.card-footer').hide();
    new Vue({ 
      el: '#content'
    });
  });
</script>
@endpush
