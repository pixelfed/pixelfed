@extends('layouts.app')

@section('content')
<div class="container notification-page" style="min-height: 60vh;">
  <div class="col-12 col-md-8 offset-md-2">
    <div class="card mt-3">
      <div class="card-body p-0">
        <ul class="nav nav-tabs d-flex text-center">
          <li class="nav-item flex-fill">
            <a class="nav-link font-weight-bold text-uppercase active" href="{{route('notifications.following')}}">Following</a>
          </li>
          <li class="nav-item flex-fill">
            <a class="nav-link font-weight-bold text-uppercase" href="{{route('notifications')}}">My Notifications</a>
          </li>
        </ul>
      </div>
    </div>
    <div class="">
{{--       <div class="card-header bg-white">
        <span class="font-weight-bold lead">Notifications</span>
        <span class="small float-right font-weight-bold">
          <a href="?a=comment" class="pr-4 text-muted" title="Commented on your post"><i class="fas fa-comment fa-2x"></i></a>
          <a href="?a=follow" class="pr-4 text-muted" title="Followed you"><i class="fas fa-user-plus fa-2x"></i></a>
          <a href="?a=mention" class="pr-4 text-muted" title="Mentioned you"><i class="fas fa-comment-dots fa-2x"></i></a>
          <a href="{{route('notifications')}}" class="font-weight-bold text-dark">View All</a>
        </span>
      </div> --}}
    </div>
    <ul class="list-group">

    @if($notifications->count() > 0)
      @foreach($notifications as $notification)
      @php
      if(!in_array($notification->action, ['like', 'follow'])) {
        continue;
      }
      @endphp
      <li class="list-group-item notification border-0">
        @switch($notification->action)

        @case('like')
          <span class="notification-icon pr-3">
            <img src="{{optional($notification->actor, function($actor) {
              return $actor->avatarUrl(); }) }}" width="32" class="rounded-circle">
          </span>
          <span class="notification-text">
            <a class="font-weight-bold text-dark" href="{{$notification->actor->url()}}">{{$notification->actor->username}}</a>

            {{__('liked a post by')}}
            
            <a class="font-weight-bold text-dark" href="{{$notification->item->profile->url()}}">{{$notification->item->profile->username}}</a>

            <span class="text-muted notification-timestamp pl-1">{{$notification->created_at->diffForHumans(null, true, true, true)}}</span>
          </span>
          <span class="float-right notification-action">
            @if($notification->item_id && $notification->item_type == 'App\Status')
              <a href="{{$notification->status->url()}}"><img src="{{$notification->status->thumb()}}" width="32" height="32"></a>
            @endif
          </span>
        @break

        @case('follow')
          <span class="notification-icon pr-3">
            <img src="{{$notification->actor->avatarUrl()}}" width="32" class="rounded-circle">
          </span>
          <span class="notification-text">
            <a class="font-weight-bold text-dark" href="{{$notification->actor->url()}}">{{$notification->actor->username}}</a>

            {{__('started following')}}
            
            <a class="font-weight-bold text-dark" href="{{$notification->item->url()}}">{{$notification->item->username}}</a>

            <span class="text-muted notification-timestamp pl-1">{{$notification->created_at->diffForHumans(null, true, true, true)}}</span>
          </span>
        @break

        @endswitch
      </li>
      @endforeach
    </ul>

      <div class="d-flex justify-content-center my-4">
        {{$notifications->links()}}
      </div>
    @else
      <div class="mt-4">
        <div class="alert alert-info font-weight-bold">No unread notifications found.</div>
      </div>
    @endif
  </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript" src="{{mix('js/activity.js')}}"></script>
@endpush
