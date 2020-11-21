@extends('site.help.partial.template', ['breadcrumb'=>'Tagging People'])

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Tagging People</h3>
  </div>
  <hr>
  <p class="lead">Tag people in your posts without mentioning them in the caption.</p>
  <div class="py-4">
    <p class="font-weight-bold h5 pb-3">Tagging People in Posts</p>
    <ul>
      <li class="mb-3 ">You can only tag <span class="font-weight-bold">local</span> and <span class="font-weight-bold">public</span> accounts who haven't blocked you.</li>
      <li class="mb-3 ">You can tag up to <span class="font-weight-bold">10</span> people.</li>
    </ul>
  </div>
  <hr>
  <div class="card bg-primary border-primary" style="box-shadow: none !important;border: 3px solid #08d!important;">
    <div class="card-header text-light font-weight-bold h4 p-4 bg-primary">Tagging Tips</div>
    <div class="card-body bg-white p-3">
      <ul class="pt-3">
        <li class="lead  mb-4">Tagging someone will send them a notification.</li>
        <li class="lead  mb-4">You can untag yourself from posts.</li>
        <li class="lead ">Only tag people you know.</li>

      </ul>
    </div>
  </div>
@endsection