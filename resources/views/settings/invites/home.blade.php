@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Invites</h3>
    <p class="lead">Send email invites to your friends and family!</p>
  </div>
  <hr>
  @if($invites->count() > 0) 
  <table class="table table-light">
    <thead>
      <tr>
        <th scope="col">#</th>
        <th scope="col">Email</th>
        <th scope="col">Valid For</th>
        <th scope="col">Actions</th>
      </tr>
    </thead>
    <tbody>
      @foreach($invites as $invite)
      <tr>
        <th scope="row">{{$invite->id}}</th>
        <td>{{$invite->email}}</td>
        <td>{{$invite->message}}</td>
        <td>
          @if($invite->used_at == null)
          <button class="btn btn-outline-danger btn-sm">Delete</button>
          @endif
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @else
  <div class="d-flex align-items-center justify-content-center text-center pb-4">
  	<div>
      <p class="pt-5"><i class="far fa-envelope-open text-lighter fa-6x"></i></p>
      <p class="lead">You haven't invited anyone yet.</p>
      <p><a class="btn btn-primary btn-lg py-0 font-weight-bold" href="{{route('settings.invites.create')}}">Invite someone</a></p>
	  	<p class="font-weight-lighter text-muted">You have <b class="font-weight-bold text-dark">{{$limit - $used}}</b> invites left.</p>
  	</div>
  </div>
  @endif
@endsection