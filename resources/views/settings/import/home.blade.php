@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Import</h3>
  </div>
  <hr>
  <section>
    <p class="lead">Account Import allows you to import your data from a supported service. <a href="#">Learn more.</a></p>
    <p class="alert alert-warning"><strong>Warning: </strong> Imported posts will not appear on timelines or be delivered to followers.</p>
  </section>
  <section class="mt-4">
    <p class="small text-muted font-weight-bold text-uppercase mb-3">Supported Services</p>
    <p class="">
      <a class="btn btn-outline-primary font-weight-bold" href="{{route('settings.import.ig')}}">Import from Instagram</a>
    </p>
    <hr>
    <p class="small text-muted font-weight-bold text-uppercase mb-3">Coming Soon</p>
    <p class="">
      <a class="btn btn-outline-secondary font-weight-bold disabled" href="#">Import from Pixelfed</a>
    </p>
    <p class="">
      <a class="btn btn-outline-secondary font-weight-bold disabled" href="#">Import from Mastodon</a>
    </p>
  </section>
@endsection