@extends('layouts.app')

@section('content')

<div class="container px-0 mt-0 mt-md-4 mb-md-5 pb-md-5">
  <div class="col-12 px-0 col-md-8 offset-md-2">

    <div class="card">
      <div class="card-header lead font-weight-bold bg-white">
        Report
      </div>
      <div class="card-body">
        <div class="p-3 text-center">
          <p class="lead">Please select one of the following options. </p>
        </div>
        <div class="row">
          <div class="col-12 col-md-8 offset-md-2 my-3">
            <p><a class="btn btn-light btn-block p-4 font-weight-bold" href="{{route('report.not-interested', ['type' => request()->query('type'),'id' => request()->query('id')])}}">
              I'm not interested in this content
            </a></p>
          </div>
          @switch(request()->query('type'))

          @case('comment')
          <div class="col-12 col-md-8 offset-md-2 mb-3">
            <p><a class="btn btn-light btn-block p-4 font-weight-bold" href="{{route('report.spam.comment', ['type' => request()->query('type'),'id' => request()->query('id')])}}">
              This comment contains spam
            </a></p>
          </div>
          <div class="col-12 col-md-8 offset-md-2 my-3">
            <p>
              <a class="btn btn-light btn-block p-4 font-weight-bold" href="{{route('report.sensitive.comment', ['type' => request()->query('type'),'id' => request()->query('id')])}}">
                This comment contains sensitive content
              </a>
            </p>
          </div>
          <div class="col-12 col-md-8 offset-md-2 my-3">
            <p>
              <a class="btn btn-light btn-block p-4 font-weight-bold" href="{{route('report.abusive.comment', ['type' => request()->query('type'),'id' => request()->query('id')])}}">
                It’s abusive or harmful
              </a>
            </p>
          </div>
          @break
          @case('post')
          <div class="col-12 col-md-8 offset-md-2 mb-3">
            <p>
              <a class="btn btn-light btn-block p-4 font-weight-bold" href="{{route('report.spam.post', ['type' => request()->query('type'),'id' => request()->query('id')])}}">
                This post contains spam
              </a>
            </p>
          </div>
          <div class="col-12 col-md-8 offset-md-2 my-3">
            <p>
              <a class="btn btn-light btn-block p-4 font-weight-bold" href="{{route('report.sensitive.post', ['type' => request()->query('type'),'id' => request()->query('id')])}}">
                This post contains sensitive content
              </a>
            </p>
          </div>
          <div class="col-12 col-md-8 offset-md-2 my-3">
            <p>
              <a class="btn btn-light btn-block p-4 font-weight-bold" href="{{route('report.abusive.post', ['type' => request()->query('type'),'id' => request()->query('id')])}}">
                This post is abusive or harmful
              </a>
            </p>
          </div>
          @break
          @case('user')
          <div class="col-12 col-md-8 offset-md-2 mb-3">
            <p>
              <a class="btn btn-light btn-block p-4 font-weight-bold" href="{{route('report.spam.profile', ['type' => request()->query('type'),'id' => request()->query('id')])}}">
                This users profile contains spam
              </a>
            </p>
          </div>
          <div class="col-12 col-md-8 offset-md-2 my-3">
            <p>
              <a class="btn btn-light btn-block p-4 font-weight-bold" href="{{route('report.sensitive.profile', ['type' => request()->query('type'),'id' => request()->query('id')])}}">
                This users profile contains sensitive content
              </a>
            </p>
          </div>
          <div class="col-12 col-md-8 offset-md-2 my-3">
            <p>
              <a class="btn btn-light btn-block p-4 font-weight-bold" href="{{route('report.abusive.profile', ['type' => request()->query('type'),'id' => request()->query('id')])}}">
                This profile is abusive or harmful
              </a>
            </p>
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
