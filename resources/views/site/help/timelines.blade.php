@extends('site.help.partial.template', ['breadcrumb'=>'Timelines'])

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Timelines</h3>
  </div>
  <hr>
  <p class="lead">Timelines are chronological feeds of posts.</p>
  <p class="font-weight-bold h5 py-3">Pixelfed has 2 different timelines:</p>

  <ul>
    <li class="lead">
      <span class="font-weight-bold"><i class="fas fa-home text-muted mr-2"></i> Personal</span>
      <span class="px-2">&mdash;</span>
      <span class="font-weight-light">Timeline with posts from accounts you follow</span>
    </li>
    <li class="lead">
      <span class="font-weight-bold"><i class="far fa-map text-muted mr-2"></i> Public</span>
      <span class="px-2">&mdash;</span>
      <span class="font-weight-light">Timeline with posts from other users on the same instance</span>
    </li>
    {{-- <li class="lead text-muted">
      <span class="font-weight-bold"><i class="fas fa-globe text-muted mr-2"></i> Network</span>
      <span class="px-2">&mdash;</span>
      <span class="font-weight-light text-muted">Timeline with posts from local and remote accounts - coming soon!</span>
    </li> --}}
  </ul>
  <div class="py-3"></div>
  <div class="card bg-primary border-primary" style="box-shadow: none !important;border: 3px solid #08d!important;">
    <div class="card-header text-light font-weight-bold h4 p-4 bg-primary">Timeline Tips</div>
    <div class="card-body bg-white p-3">
      <ul class="pt-3">
        <li class="lead mb-4">You can mute or block accounts to prevent them from appearing in timelines.</li>
        <li class="lead mb-4">You can create <span class="font-weight-bold">Unlisted</span> posts that don't appear in public timelines.</li>

      </ul>
    </div>
  </div>
@endsection