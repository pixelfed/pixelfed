@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Blocked Users</h3>
  </div>
  <hr>
  <div class="form-group pb-1">
    <p>
      <a class="btn btn-outline-secondary py-0 font-weight-bold" href="{{route('settings.privacy.muted-users')}}">Muted Users</a>
      <a class="btn btn-outline-primary py-0 font-weight-bold" href="{{route('settings.privacy.blocked-users')}}">Blocked Users</a>
      {{-- <a class="btn btn-outline-secondary py-0 font-weight-bold" href="{{route('settings.privacy.blocked-keywords')}}">Blocked keywords</a>
      <a class="btn btn-outline-secondary py-0 font-weight-bold" href="{{route('settings.privacy.blocked-instances')}}">Blocked instances</a> --}}
    </p>
  </div>
  @if($users->count() > 0)
  <ul class="list-group list-group-flush">
    @foreach($users as $user)
    <li class="list-group-item">
      <div class="d-flex justify-content-between align-items-center font-weight-bold">
        <span><a href="{{$user->url()}}" class="text-decoration-none text-dark"><img class="rounded-circle mr-3" src="{{$user->avatarUrl()}}" width="32px">{{$user->username}}</a></span>
        <span class="btn-group">
          <form method="post">
            @csrf
            <input type="hidden" name="profile_id" value="{{$user->id}}">
            <button type="submit" class="btn btn-outline-secondary btn-sm px-3 font-weight-bold">Unblock</button>
          </form>
        </span>
      </div> 
    </li>
    @endforeach
  </ul>
  <div class="d-flex justify-content-center mt-3 font-weight-bold">
    {{$users->links()}}
  </div>
  @else
  <p class="lead">You are not blocking any accounts.</p>
  @endif

@endsection