@extends('layouts.app')

@section('content')

<div class="container mt-4 mb-5 pb-5">
  <div class="col-12 col-md-8 offset-md-2">
    <div class="card">
      <div class="card-header lead font-weight-bold">
        Report Spam
      </div>
      <div class="card-body">
        <div class="p-5 text-center">
          <p class="lead">Please select one of the following options.</p>
        </div>
        <div class="row">
          @switch(request()->query('type'))

          @case('comment')
          <div class="col-12 col-md-8 offset-md-2 mb-3">
            <p><a class="btn btn-light btn-block p-4 font-weight-bold" href="{{route('report.spam.comment')}}">
              This comment contains spam
            </a></p>
          </div>
          @break
          @case('post')
          <div class="col-12 col-md-8 offset-md-2 mb-3">
            <p><a class="btn btn-light btn-block p-4 font-weight-bold" href="{{route('report.spam.post')}}">
              This post contains spam
            </a></p>
          </div>
          @break
          @case('user')
          <div class="col-12 col-md-8 offset-md-2 mb-3">
            <p><a class="btn btn-light btn-block p-4 font-weight-bold" href="{{route('report.spam.profile')}}">
              This users profile contains spam
            </a></p>
          </div>
          @break
          @endswitch
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