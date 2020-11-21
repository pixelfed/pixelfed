@extends('site.help.partial.template', ['breadcrumb'=>'Discover'])

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Discover</h3>
  </div>
  <hr>
  <p class="lead">Discover new posts, people and topics.</p>
  <div class="py-4">
    <p class="font-weight-bold h5 pb-3">How to use Discover</p>
    <ul>
      <li class="mb-3 ">Click the <i class="far fa-compass fa-sm"></i> icon.</li>
      <li class="mb-3 ">View the latest posts.</li>
    </ul>
  </div>
  <div class="py-4">
    <p class="font-weight-bold h5 pb-3">Discover Categories <span class="badge badge-success">NEW</span></p>
    <p>Discover Categories are a new feature that may not be supported on every Pixelfed instance.</p>
    <ul>
      <li class="mb-3 ">Click the <i class="far fa-compass fa-sm"></i> icon.</li>
      <li class="mb-3 ">On the discover page, you will see a list of Category cards that links to each Discover Category.</li>
    </ul>
  </div>
  <div class="card bg-primary border-primary" style="box-shadow: none !important;border: 3px solid #08d!important;">
    <div class="card-header text-light font-weight-bold h4 p-4 bg-primary">Discover Tips</div>
    <div class="card-body bg-white p-3">
      <ul class="pt-3">
        <li class="lead  mb-4">To make your posts more discoverable, add hashtags to your posts.</li>
        <li class="lead  mb-4">Any public posts that contain a hashtag may be included in discover pages.</li>

      </ul>
    </div>
  </div>
@endsection