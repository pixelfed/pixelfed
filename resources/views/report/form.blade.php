@extends('layouts.app')

@section('content')

<div class="container mt-4 mb-5 pb-5">
  <div class="col-12 col-md-8 offset-md-2">

    <div class="card my-5">
      <div class="card-body">
        <p class="mb-0 font-weight-bold">This feature is not yet ready for production. Please try again later.</p>
      </div>
    </div>

    <div class="card sr-only">
      <div class="card-header lead font-weight-bold">
        Report
      </div>
      <div class="card-body">
        <div class="p-5 text-center">
          <p class="lead">Please select one of the following options.</p>
        </div>
        <div class="row">
          <div class="col-12 col-md-8 offset-md-2 my-3">
            <p><a class="btn btn-light btn-block p-4 font-weight-bold" disabled>
              I'm not interested in this content
            </a></p>
          </div>
          <div class="col-12 col-md-8 offset-md-2 my-3">
            <p><a class="btn btn-light btn-block p-4 font-weight-bold">
              It's spam
            </a></p>
          </div>
          <div class="col-12 col-md-8 offset-md-2 my-3">
            <p><a class="btn btn-light btn-block p-4 font-weight-bold">
              It displays a sensitive image
            </a></p>
          </div>
          <div class="col-12 col-md-8 offset-md-2 my-3">
            <p><a class="btn btn-light btn-block p-4 font-weight-bold">
              It's abusive or harmful
            </a></p>
          </div>
          <div class="col-12 col-md-8 offset-md-2 my-3">
            <p><a class="font-weight-bold" href="#">
              Learn more
            </a> about our reporting guidelines and policy.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection