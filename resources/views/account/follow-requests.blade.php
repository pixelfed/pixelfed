@extends('layouts.app')

@section('content')
<div class="bg-white py-4">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center">
      <div></div>
      <a href="/account/activity" class="cursor-pointer font-weight-bold text-dark">Notifications</a>
      <a href="/account/follow-requests" class="cursor-pointer font-weight-bold text-primary">Follow Requests</a>
      <div></div>
    </div>
  </div>
</div>
<div class="container notification-page" style="min-height: 60vh;">
  <div class="col-12 col-md-8 offset-md-2">
    @if($followers->count() > 0)
    <ul class="list-group">
      @foreach($followers as $follow)
      <li class="list-group-item notification border-0">
          <span class="notification-icon pr-3">
            <img src="{{$follow->follower->avatarUrl()}}" width="32" class="rounded-circle">
          </span>
          <span class="notification-text">
            <a class="font-weight-bold text-dark" href="{{$follow->follower->url()}}">{{$follow->follower->username}}</a> {{__('wants to follow you')}}
            <span class="text-muted notification-timestamp pl-1">{{$follow->created_at->diffForHumans(null, true, true)}}</span>
          </span>
          <span class="float-right">
            <div class="btn-group" role="group" aria-label="Basic example">
              <button type="button" class="btn btn-outline-default request-action" data-id="{{$follow->id}}" data-action="reject"><i class="fas fa-times text-danger"></i></button>
              <button type="button" class="btn btn-outline-default request-action" data-id="{{$follow->id}}" data-action="accept"><i class="fas fa-check text-success"></i></button>
            </div>
          </span>
      </li>
      @endforeach
    </ul>

    <div class="d-flex justify-content-center my-4">
      {{$followers->links()}}
    </div>
    @else
    <div class="text-center pt-5">
      <p class="font-weight-bold text-muted">You don't have any follow requests</p>
    </div>
    @endif

  </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
App.boot();
$(document).ready(function() {
  $(document).on('click', '.request-action', function(e) {
    e.preventDefault();
    let el = $(this);
    let action = el.data('action');
    let id = el.data('id');

    axios.post(window.location.href, {
      action: action,
      id: id
    }).then((res) => {
      if(action == 'accept') {
        swal('Successfully accepted!', 'You have successfully approved that follow request.', 'success');
      } else {
        swal('Successfully rejected!', 'You have successfully rejected that follow request.', 'success');
      }
    }).catch((res) => {
      swal('Oops!', 'Something went wrong, please try again later', 'error');
    });
    let parent = el.parents().eq(2);
    parent.fadeOut();
  });
});
</script>
@endpush
