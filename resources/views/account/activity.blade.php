@extends('layouts.app')

@section('content')
<div class="container notification-page" style="min-height: 60vh;">
  <div class="col-12 col-md-8 offset-md-2">
    <div class="card mt-3">
      <div class="card-body p-0">
        <ul class="nav nav-pills d-flex text-center">
        
          {{-- <li class="nav-item flex-fill">
            <a class="nav-link font-weight-bold text-uppercase" href="#">Following</a>
          </li> --}} 
        
          <li class="nav-item flex-fill">
            <a class="nav-link font-weight-bold text-uppercase active" href="{{route('notifications')}}">My Notifications</a>
          </li>
          <li class="nav-item flex-fill">
            <a class="nav-link font-weight-bold text-uppercase" href="{{route('follow-requests')}}">Follow Requests</a>
          </li> 
        </ul>
      </div>
    </div>
    <div class="">
      <div class="dropdown text-right mt-2">
        <a class="btn btn-link btn-sm dropdown-toggle font-weight-bold text-dark" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          Filter
        </a>

        <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                <a href="?a=comment" class="dropdown-item font-weight-bold" title="Commented on your post">
                  Comments only
                </a>
                <a href="?a=follow" class="dropdown-item font-weight-bold" title="Followed you">
                  New Followers only
                </a>
                <a href="?a=mention" class="dropdown-item font-weight-bold" title="Mentioned you">
                  Mentions only
                </a>
                <a href="{{route('notifications')}}" class="dropdown-item font-weight-bold text-dark">
                  View All
                </a>
        </div>
      </div>
    </div>
    <ul class="list-group">

    @if($notifications->count() > 0)
      @foreach($notifications as $notification)
      <li class="list-group-item notification border-0">
        @switch($notification->action)

        @case('like')
          <span class="notification-icon pr-3">
            <img src="{{optional($notification->actor, function($actor) {
              return $actor->avatarUrl(); }) }}" width="32px" class="rounded-circle">
          </span>
          <span class="notification-text">
            {!! $notification->rendered !!}
            <span class="text-muted notification-timestamp pl-1">{{$notification->created_at->diffForHumans(null, true, true, true)}}</span>
          </span>
          <span class="float-right notification-action">
            @if($notification->item_id && $notification->item_type == 'App\Status')
              <a href="{{$notification->status->url()}}"><img src="{{$notification->status->thumb()}}" width="32px" height="32px"></a>
            @endif
          </span>
        @break

        @case('follow')
          <span class="notification-icon pr-3">
            <img src="{{$notification->actor->avatarUrl()}}" width="32px" class="rounded-circle">
          </span>
          <span class="notification-text">
            {!! $notification->rendered !!}
            <span class="text-muted notification-timestamp pl-1">{{$notification->created_at->diffForHumans(null, true, true, true)}}</span>
          </span>
          @if($notification->actor->followedBy(Auth::user()->profile) == false)
          <span class="float-right notification-action">
           <form class="follow-form" method="post" action="/i/follow" style="display: inline;" data-id="{{$notification->actor->id}}" data-action="follow">
              @csrf
              <input type="hidden" name="item" value="{{$notification->actor->id}}">
              <button class="btn btn-primary font-weight-bold px-4 py-0" type="submit">Follow</button>
            </form>
          </span>
          @endif
        @break

        @case('comment')
          <span class="notification-icon pr-3">
            <img src="{{$notification->actor->avatarUrl()}}" width="32px" class="rounded-circle">
          </span>
          <span class="notification-text">
            {!! $notification->rendered !!}
            <span class="text-muted notification-timestamp pl-1">{{$notification->created_at->diffForHumans(null, true, true, true)}}</span>
          </span>
          <span class="float-right notification-action">
            @if($notification->item_id)
              <a href="{{$notification->status->parent()->url()}}">
                <div class="notification-image" style="background-image: url('{{$notification->status->parent()->thumb()}}')"></div>
              </a>
            @endif
          </span>
        @break

        @case('mention')
          <span class="notification-icon pr-3">
            <img src="{{$notification->status->profile->avatarUrl()}}" width="32px" class="rounded-circle">
          </span>
          <span class="notification-text">
            {!! $notification->rendered !!}
            <span class="text-muted notification-timestamp pl-1">{{$notification->created_at->diffForHumans(null, true, true, true)}}</span>
          </span>
          <span class="float-right notification-action">
            @if($notification->item_id && $notification->item_type === 'App\Status')
              @if(is_null($notification->status->in_reply_to_id))
              <a href="{{$notification->status->url()}}">
                <div class="notification-image" style="background-image: url('{{$notification->status->thumb()}}')"></div>
              </a>
              @else
              <a href="{{$notification->status->parent()->url()}}">
                <div class="notification-image" style="background-image: url('{{$notification->status->parent()->thumb()}}')"></div>
              </a>
              @endif
            @endif
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
