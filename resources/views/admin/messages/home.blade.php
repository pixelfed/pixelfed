@extends('admin.partial.template-full')

@section('section')
<div class="title">
	<h3 class="font-weight-bold d-inline-block">Messages</h3>
</div>

<hr>
<div class="table-responsive">
  <table class="table">
    <thead class="bg-light">
      <tr>
        <th scope="col">#</th>
        <th scope="col">User</th>
        <th scope="col">Message</th>
        <th scope="col">Created</th>
      </tr>
    </thead>
    <tbody>
      @foreach($messages as $msg)
      <tr>
        <td>
          <a href="/i/admin/messages/show/{{$msg->id}}" class="btn btn-sm btn-outline-primary">
           	{{$msg->id}}
          </a>
        </td>
        <td class="font-weight-bold"><a href="{{$msg->user->url()}}">{{$msg->user->username}}</a></td>
        <td class="font-weight-bold">{{str_limit($msg->message, 40)}}</td>
        <td class="font-weight-bold">{{$msg->created_at->diffForHumans()}}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
{{$messages->links()}}
@endsection