@extends('site.help.partial.template', ['breadcrumb'=>'Timelines'])

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Timelines</h3>
  </div>
  <hr>
  <p class="lead">Timelines are chronological feeds of posts from accounts you follow or from other instances.</p>
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
@endsection