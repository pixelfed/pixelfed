@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Blocked Instances</h3>
  </div>
  <hr>
  <div class="form-group pb-1">
    <p>
      <a class="btn btn-outline-secondary py-0 font-weight-bold" href="{{route('settings.privacy.muted-users')}}">Muted Users</a>
      <a class="btn btn-outline-secondary py-0 font-weight-bold" href="{{route('settings.privacy.blocked-users')}}">Blocked Users</a>
      <a class="btn btn-outline-primary py-0 font-weight-bold" href="{{route('settings.privacy.blocked-instances')}}">Blocked Instances</a>
    </p>
  </div>
  <form method="post">
  </form>

@endsection