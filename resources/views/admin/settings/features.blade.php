@extends('admin.partial.template')

@include('admin.settings.sidebar')

@section('section')
  <div class="title">
    <h3 class="font-weight-bold">Features</h3>
    <p class="lead">Enable or disable core features</p>
  </div>
  <hr>
  <p class="alert alert-warning">
    <strong>Feature Unavailable:</strong> This feature will be released in a future version.
  </p>
@endsection