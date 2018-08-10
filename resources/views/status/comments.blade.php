@extends('layouts.app')

@section('content')

<div class="container px-0 mt-md-4">
  <div class="col-12 col-md-8 offset-md-2">
    
    <div class="card">
      <div class="card-body">
        <p class="mb-0">
          <img class="img-thumbnail mr-2" src="{{$user->avatarUrl()}}" width="24px" height="24px" style="border-radius:12px;">
          <span class="font-weight-bold pr-1"><bdi><a class="text-dark" href="{{$status->profile->url()}}">{{ str_limit($status->profile->username, 15)}}</a></bdi></span>
          <span class="comment-text">{!! $status->rendered ?? e($status->caption) !!} <a href="{{$status->url()}}" class="text-dark small font-weight-bold float-right pl-2">{{$status->created_at->diffForHumans(null, true, true ,true)}}</a></span>
        </p>
        <hr>
        <div class="comments">
          @foreach($replies as $item)
          <p class="mb-2">
            <span class="font-weight-bold pr-1">
              <img class="img-thumbnail mr-2" src="{{$item->profile->avatarUrl()}}" width="24px" height="24px" style="border-radius:12px;">
              <bdi><a class="text-dark" href="{{$item->profile->url()}}">{{ str_limit($item->profile->username, 15)}}</a></bdi>
            </span>
            <span class="comment-text">
              {!! $item->rendered ?? e($item->caption) !!} 
              <a href="{{$item->url()}}" class="text-dark small font-weight-bold float-right pl-2">
                {{$item->created_at->diffForHumans(null, true, true ,true)}}
              </a>
            </span>
          </p>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>

@endsection