@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Blocked Keywords</h3>
  </div>
  <hr>
  <div class="form-group pb-1">
    <p>
      <a class="btn btn-outline-secondary py-0 font-weight-bold" href="{{route('settings.privacy.muted-users')}}">Muted Users</a>
      <a class="btn btn-outline-secondary py-0 font-weight-bold" href="{{route('settings.privacy.blocked-users')}}">Blocked Users</a>
      <a class="btn btn-outline-primary py-0 font-weight-bold" href="{{route('settings.privacy.blocked-keywords')}}">Blocked keywords</a>
      <a class="btn btn-outline-secondary py-0 font-weight-bold" href="{{route('settings.privacy.blocked-instances')}}">Blocked instances</a>
    </p>
  </div>
  <div class="alert alert-warning border-0">
    <p class="font-weight-bold mb-0">Unavailable</p>
    <p class="mb-0">This setting will be released in a future version.</p>
  </div>

@endsection