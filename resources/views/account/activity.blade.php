@extends('layouts.app')

@section('content')
<div class="container notification-page" style="min-height: 60vh;">
  <div class="col-12 col-md-8 offset-2">
    <ul class="list-group">

      @foreach($notifications as $notification)
      <li class="list-group-item notification">
        @switch($notification->action)

        @case('like')
          <span class="notification-icon pr-3">
            <img src="{{$notification->actor->avatarUrl()}}" width="32px" class="rounded-circle">
          </span>
          <span class="notification-text">
            {!! $notification->rendered !!}
            <span class="text-muted notification-timestamp pl-1">{{$notification->created_at->diffForHumans(null, true, true, true)}}</span>
          </span>
          <span class="float-right notification-action">
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
          <span class="float-right notification-action">
           <form class="follow-form" method="post" action="/i/follow" style="display: inline;" data-id="{{$notification->actor->id}}" data-action="follow">
              @csrf
              <input type="hidden" name="item" value="{{$notification->actor->id}}">
              <button class="btn btn-primary font-weight-bold px-4 py-0" type="submit">Follow</button>
            </form>
          </span>
        @break
        @endswitch
      </li>
      @endforeach
    </ul>
  </div>
</div>
@endsection