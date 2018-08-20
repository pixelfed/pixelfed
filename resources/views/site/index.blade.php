@extends('layouts.app')

@section('content')


<div class="jumbotron jumbotron-fluid bg-alt text-white my-0">
  <div class="container text-center mt-5">
    <h1 class="display-4 font-weight-ultralight">Image Sharing for Everyone</h1>
    <p class="h3 font-weight-ultralight">A free and ethical photo sharing platform.</p>
  </div>
</div>
<div class="py-3"></div>
<div class="container slim d-none d-md-block">
  <div class="row">
    <div class="col-12 col-md-4 mb-4">
      <div class="card bg-transparent" style="box-shadow: none;border:1px solid #fff">
        <div class="card-body text-white text-center">
          <p class="font-weight-bold lead mb-0">
            Ad Free
          </p>
          <p class="font-weight-light mb-0">No Ads or Trackers</p>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4 mb-4">
      <div class="card bg-transparent" style="box-shadow: none;border:1px solid #fff">
        <div class="card-body text-white text-center">
          <p class="font-weight-bold lead mb-0">
            Chronological
          </p>
          <p class="font-weight-light mb-0">Timelines in order</p>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4 mb-4">
      <div class="card bg-transparent" style="box-shadow: none;border:1px solid #fff">
        <div class="card-body text-white text-center">
          <p class="font-weight-bold lead mb-0">
            Federated
          </p>
          <p class="font-weight-light mb-0">A network of millions</p>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card bg-transparent" style="box-shadow: none;border:1px solid #fff">
        <div class="card-body text-white text-center">
          <p class="font-weight-bold lead mb-0">
            Discover
          </p>
          <p class="font-weight-light mb-0">Discover popular posts</p>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card bg-transparent" style="box-shadow: none;border:1px solid #fff">
        <div class="card-body text-white text-center">
          <p class="font-weight-bold lead mb-0">
            Photo Filters
          </p>
          <p class="font-weight-light mb-0">Add an optional filter</p>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card bg-transparent" style="box-shadow: none;border:1px solid #fff">
        <div class="card-body text-white text-center">
          <p class="font-weight-bold lead mb-0">
            Stories
          </p>
          <p class="font-weight-light mb-0">Coming Soon!</p>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="py-5 d-none d-md-block"></div>
<div class="container slim d-flex justify-content-center">
  <div class="card" style="width:500px;">
    <div class="card-header d-inline-flex align-items-center bg-white">
        <img src="/storage/avatars/default.png" width="32px" height="32px" style="border-radius: 32px; border: 1px solid #ccc">
        <span class="username font-weight-bold pl-2 text-dark">
          username
        </span>
    </div>
    <div class="card-body p-0">
      <img class="img-fluid" src="/img/sample-post.jpeg">
    </div>
    <div class="card-footer bg-white">
      <div class="likes font-weight-bold mb-2">
        <span class="like-count">124k</span> likes
      </div>
      <div class="caption">
        <p class="mb-1">
          <span class="username font-weight-bold">
            <bdi>username</bdi>
          </span>
          <span class="caption-body" data-processed="false">Hello world! <a href="#">#introduction</a></span>
        </p>
      </div>
    </div>
  </div>
</div>
<div class="py-5 my-5"></div>
<div class="container">
  <div class="row d-flex align-items-center">
    <div class="col-12 col-md-5">
      <img src="/img/online_world.svg" class="img-fluid">
    </div>
    <div class="col-12 col-md-7 text-center">
      <h1 class="h1">Create. Discover. Share.</h1>
      <p class="h3 font-weight-light">
        A feature rich photo sharing experience <br>
      </p>
    </div>
  </div>
</div>
<div class="py-5 my-5"></div>
<div class="bg-white">
  
<section class="container slim mt-5">
  <div class="row py-5">
    <div class="col-12 my-5 py-5">
      
        <div class="text-center">
          <h1 class="display-4">Powered by People</h1>
          <p class="h3 font-weight-light">
            Pixelfed is an open-source, federated platform. <br>
            You can run your own instance or join one. <br>
          </p>
{{--           <p class="pt-5 mb-0">
            <a class="btn btn-outline-secondary btn-lg font-weight-ultralight mr-3" href="{{route('site.about')}}">About this Instance</a>
            <a class="btn btn-outline-secondary btn-lg font-weight-ultralight" href="{{route('login')}}">Login</a>
          </p> --}}
        </div>


    </div>
  </div>
</section>
</div>
@endsection

@push('meta')
<meta property="og:description" content="Federated Image Sharing">
<style type="text/css">
  .container.slim {
    width: auto;
    max-width: 680px;
    padding: 0 15px;
  }
  .bg-alt {
    background: #FEAC5E;
    background: -webkit-linear-gradient(to right, #4BC0C8, #C779D0, #FEAC5E);
    background: linear-gradient(to right, #4BC0C8, #C779D0, #FEAC5E);
  }
  .jumbotron.bg-alt:before {
    content: "";
    position: absolute;
    z-index: -1;
    width: 100%;
    height: 100%;
    min-height: 900px;
    top: 0;
    -webkit-transform: skewY(-12deg); 
    transform: skewY(-12deg); 
    background: #FEAC5E;
    background: -webkit-linear-gradient(to right, #4BC0C8, #C779D0, #FEAC5E);
    background: linear-gradient(to right, #4BC0C8, #C779D0, #FEAC5E);
  }
</style>
@endpush
