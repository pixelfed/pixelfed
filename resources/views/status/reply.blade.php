@extends('layouts.app')

@section('content')

<div class="container reply-container">
  <div class="col-12 col-md-8 offset-md-2 mt-4">
      <div class="card shadow-none border">
        @php($authed = request()->user())
        @php($pid = $authed ? request()->user()->profile_id : null)
        @php($gp = $status->parent()->parent())
        @if($gp)
        <div class="card-body p-0 m-0 bg-light border-bottom">
        	@if(
                !in_array($gp->scope, ['public', 'unlisted', 'private']) ||
                ($gp->scope == 'private' && !$authed) ||
                ($gp->scope == 'private' && ($gp->profile_id != $pid && \App\Services\FollowerService::follows($pid, $gp->profile_id) == false))
            )
        		<p class="text-center mb-0 py-5 font-weight-bold">This status cannot be viewed at this time.</p>
        	@else
          <div class="d-flex p-0 m-0 align-items-center">
            @if($gp->media()->count())
            <img src="{{$gp->thumb()}}" width="150px" height="150px" class="post-thumbnail" onerror="this.onerror=null;this.src='/storage/no-preview.png?v=0';">
            @endif
            <div class="p-4 w-100">
              <div class="">
                <div class="media">
                  <img src="{{$gp->profile->avatarUrl()}}" class="rounded-circle img-thumbnail mb-1 mr-3" width="30px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';">
                  <div class="media-body">
                    <span class="font-weight-bold" v-pre>{{$gp->profile->username}}</span>
                    <div class="">
                      <p class="w-100 text-break" v-pre>{!!$gp->rendered!!}</p>
                    </div>
                    <div class="mb-0 small">
                      <a href="{{$gp->url()}}" class="text-muted">
                        {{$gp->created_at->diffForHumans()}}
                      </a>
                    </div>
                  </div>
                  <a class="float-right" href="{{$gp->url()}}"><i class="far fa-share-square"></i></a>
                </div>
              </div>
            </div>
          </div>
          @endif
        </div>
        @endif

        @php($parent = $status->parent())
        <div class="card-body p-0 m-0 bg-light border-bottom">
            @if(
                !in_array($parent->scope, ['public', 'unlisted', 'private']) ||
                ($parent->scope == 'private' && !$authed) ||
                ($parent->scope == 'private' && ($parent->profile_id != $pid && \App\Services\FollowerService::follows($pid, $parent->profile_id) == false))
            )
        		<p class="text-center mb-0 py-5 font-weight-bold">This status cannot be viewed at this time.</p>
        	@else
          <div class="d-flex p-0 m-0 align-items-center">
            @if($parent->media()->count())
            <img src="{{$parent->thumb()}}" width="150px" height="150px" class="post-thumbnail" onerror="this.onerror=null;this.src='/storage/no-preview.png?v=0';">
            @endif
            <div class="p-4 w-100">
              <div class="">
                <div class="media">
                  <img src="{{$parent->profile->avatarUrl()}}" class="rounded-circle img-thumbnail mb-1 mr-3" width="30px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';">
                  <div class="media-body">
                    <span class="font-weight-bold" v-pre>{{$parent->profile->username}}</span>
                    <div class="">
                      <p class="w-100 text-break" v-pre>{!!$parent->rendered!!}</p>
                    </div>
                    <div class="mb-0 small">
                      <a href="{{$parent->url()}}" class="text-muted">
                        {{$parent->created_at->diffForHumans()}}
                      </a>
                    </div>
                  </div>
                  <a class="float-right" href="{{$parent->url()}}"><i class="far fa-share-square"></i></a>
                </div>
              </div>
            </div>
          </div>
          @endif
        </div>


        <div class="card-body border-bottom">
          @if($status->is_nsfw)
          <details class="cw">
            <summary class="px-3 px-md-5">
              <p class="py-5 mb-0 text-center">This comment may contain sensitive content. <span class="float-right font-weight-bold text-primary">Show</span></p>
            </summary>
            <div class="media py-5">
              <img class="mr-3 rounded-circle img-thumbnail" src="{{$status->profile->avatarUrl()}}" width="60px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';">
              <div class="media-body">
                <h5 class="mt-0 font-weight-bold" v-pre>{{$status->profile->username}}</h5>
                <p class="" v-pre>{!! $status->rendered !!}</p>
                <div class="mb-0 small">
                  <a href="{{$status->url()}}" class="text-muted">
                    {{$status->created_at->diffForHumans()}}
                  </a>
                </div>
              </div>
            </div>
          </details>
          @else
          <div class="media py-5">
            <img class="mr-3 rounded-circle img-thumbnail" src="{{$status->profile->avatarUrl()}}" width="60px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';">
            <div class="media-body">
              <h5 class="mt-0 font-weight-bold" v-pre>{{$status->profile->username}}</h5>
              <p class="" v-pre>{!! $status->rendered !!}</p>
              <div class="mb-0 small">
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

        @if($status->comments->count())
        <div class="card-body p-0 m-0 bg-light border-bottom">
          <div class="d-flex p-0 m-0 align-items-center">
            @if($status->comments()->first()->media()->count())
            <img src="{{$status->comments()->first()->thumb()}}" width="150px" height="150px" class="post-thumbnail" onerror="this.onerror=null;this.src='/storage/no-preview.png?v=0';">
            @endif
            <div class="p-4 w-100">
              <div class="">
                <div class="media">
                  <img src="{{$status->comments()->first()->profile->avatarUrl()}}" class="rounded-circle img-thumbnail mb-1 mr-3" width="30px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';">
                  <div class="media-body">
                    <span class="font-weight-bold" v-pre>{{$status->comments()->first()->profile->username}}</span>
                    <div class="">
                      <p class="w-100 text-break" v-pre>{!!$status->comments()->first()->rendered!!}</p>
                    </div>
                    <div class="mb-0 small">
                      <a href="{{$status->comments()->first()->url()}}" class="text-muted">
                        {{$status->comments()->first()->created_at->diffForHumans()}}
                      </a>
                    </div>
                  </div>
                  <a class="float-right" href="{{$status->comments()->first()->url()}}"><i class="far fa-share-square"></i></a>
                </div>
              </div>
            </div>
          </div>
        </div>
        @endif
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
