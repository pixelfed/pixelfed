@extends('layouts.app')

@section('content')
<div class="container mt-5 pt-3">
  <section>
    <p class="lead text-muted font-weight-bold">Discover People</p>
    <div class="row">
      @foreach($people as $profile)
      <div class="col-md-4">
        <div class="card">
          <div class="card-body p-4 text-center">
            <div class="avatar pb-3">
              <img src="{{$profile->avatarUrl()}}" class="img-thumbnail rounded-circle" width="64px">
            </div>
            <p class="lead font-weight-bold mb-0">{{$profile->username}}</p>
            <p class="text-muted">{{$profile->name}}</p>
            <form class="follow-form" method="post" action="/i/follow" data-id="{{$profile->id}}" data-action="follow">
              @csrf
              <input type="hidden" name="item" value="{{$profile->id}}">
              <button class="btn btn-primary font-weight-bold px-4 py-0" type="submit">Follow</button>
            </form>
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </section>
  <section class="pt-5 mt-5">
    <p class="lead text-muted font-weight-bold">Explore</p>
    <div class="profile-timeline row">
      @foreach($posts as $status)
      <div class="col-12 col-md-4 mb-4">
        <a class="card" href="{{$status->url()}}">
          <img class="card-img-top" src="{{$status->thumb()}}" width="300px" height="300px">
        </a>
      </div>
      @endforeach
    </div>
  </section>
</div>

@endsection