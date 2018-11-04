@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Import</h3>
  </div>
  <hr>
  <section>
    <p class="lead">Account Import allows you to import your data from a supported service.</p>
    <p class="alert alert-warning">Importing from another service will not impact existing data by default however you may choose to update avatar, bio or nickname fields during the process.</p>
  </section>
  <section class="mt-5">
    <p class="small text-muted font-weight-bold text-uppercase mb-3">Supported Services</p>
    <p class="">
      <a class="btn btn-outline-primary font-weight-bold" href="{{route('settings.import.ig')}}">Import from Instagram</a>
    </p>
    <hr>
    <p class="small text-muted font-weight-bold text-uppercase mb-3">Coming Soon</p>
    <p class="">
      <a class="btn btn-outline-secondary font-weight-bold disabled" href="#">Import from Mastodon</a>
    </p>
    <p class="">
      <a class="btn btn-outline-secondary font-weight-bold disabled" href="#">Import from Pleroma</a>
    </p>
    <p class="">
      <a class="btn btn-outline-secondary font-weight-bold disabled" href="#">Import from GNU/Social</a>
    </p>
  </section>
@endsection