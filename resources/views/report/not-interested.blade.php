@extends('layouts.app')

@section('content')

<div class="container px-0 mt-0 mt-md-4 mb-md-5 pb-md-5">
  <div class="col-12 px-0 col-md-8 offset-md-2">
    <div class="card">
      <div class="card-header lead font-weight-bold bg-white">
        I'm not interested in this content
      </div>
      <div class="card-body">
        <div class="p-5 text-center">
          <p class="lead">You can <b class="font-weight-bold">unfollow</b> or <b class="font-weight-bold">mute</b> a user or hashtag from appearing in your timeline. Unless the content violates our terms of service, there is nothing we can do to remove it.</p>
        </div>
{{--         <div class="col-12 col-md-8 offset-md-2">
          <p><a class="font-weight-bold" href="#">
            Learn more
          </a> about our reporting guidelines and policy.</p>
        </div> --}}
      </div>
    </div>
  </div>
</div>

@endsection