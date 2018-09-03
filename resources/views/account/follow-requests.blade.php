@extends('layouts.app')

@section('content')
<div class="container notification-page" style="min-height: 60vh;">
  <div class="col-12 col-md-8 offset-md-2">
    <div class="card mt-3">
      <div class="card-body p-0">
        <ul class="nav nav-pills d-flex text-center">
          <li class="nav-item flex-fill">
            <a class="nav-link font-weight-bold text-uppercase" href="{{route('notifications')}}">My Notifications</a>
          </li>
          <li class="nav-item flex-fill">
            <a class="nav-link font-weight-bold text-uppercase active" href="{{route('follow-requests')}}">Follow Requests</a>
          </li> 
        </ul>
      </div>
    </div>
    <ul class="list-group">
      @foreach($followers as $follow)
      <li class="list-group-item notification border-0">
          <span class="notification-icon pr-3">
            <img src="{{$follow->follower->avatarUrl()}}" width="32px" class="rounded-circle">
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

  </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
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
