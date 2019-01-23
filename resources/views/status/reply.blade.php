@extends('layouts.app')

@section('content')

<div class="container reply-container">
  <div class="col-12 col-md-8 offset-md-2 mt-4">
      <div class="card">
        <div class="card-body p-0 m-0 bg-light">
          <div class="d-flex p-0 m-0 align-items-center">
            <img src="{{$status->parent()->thumb()}}" width="150px" height="150px" class="post-thumbnail">
            <div class="p-4">
              <div>
                <img src="{{$status->parent()->profile->avatarUrl()}}" class="rounded-circle img-thumbnail mb-1 mr-1" width="30px">
                <span class="h5 font-weight-bold">{{$status->parent()->profile->username}}</span>
                <a class="float-right" href="{{$status->parent()->url()}}"><i class="far fa-share-square"></i></a>
              </div>
              <div class="">
                <p class="mb-0">{{ str_limit($status->parent()->caption, 125) }}</p>
              </div>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="media py-5">
            <img class="mr-3 rounded-circle img-thumbnail" src="{{$status->profile->avatarUrl()}}" width="60px">
            <div class="media-body">
              <h5 class="mt-0 font-weight-bold">{{$status->profile->username}}</h5>
              <p class="mb-1">{!! $status->rendered !!}</p>
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
        </div>
      </div>
  </div>
</div>

@endsection

@push('scripts')
<script type="text/javascript">
  $(document).ready(function() {
    $('.reactions').hide();
    $('.more-comments').hide();
    $('.card-footer').hide();
  });
</script>
@endpush
