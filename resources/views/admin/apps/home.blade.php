@extends('admin.partial.template-full')

@section('section')
<div class="title">
	<h3 class="font-weight-bold d-inline-block">Apps</h3>
	<span class="float-right">
		<div class="dropdown">
			<button class="btn btn-light btn-sm dropdown-toggle font-weight-bold" type="button" id="filterDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			  <i class="fas fa-filter"></i>
			</button>
			<div class="dropdown-menu dropdown-menu-right" aria-labelledby="filterDropdown">
				<a class="dropdown-item font-weight-light" href="{{route('admin.apps')}}?filter=revoked">Show only Revoked</a>
				<a class="dropdown-item font-weight-light" href="{{route('admin.apps')}}">Show all</a>
			</div>
		</div>
	</span>
</div>

<hr>
<div class="table-responsive">
  <table class="table">
    <thead class="bg-light">
      <tr>
        <th scope="col">#</th>
        <th scope="col">Owner</th>
        <th scope="col" width="25%">Name</th>
        <th scope="col">Callback URL</th>
        <th scope="col">Revoked</th>
        <th scope="col">Created</th>
      </tr>
    </thead>
    <tbody>
      @foreach($apps as $app)
      <tr>
        <td>
          <a href="/i/admin/apps/show/{{$app->id}}" class="btn btn-sm btn-outline-primary">
           	{{$app->id}}
          </a>
        </td>
        <td class="font-weight-bold"><a href="{{$app->user->url()}}">{{$app->user->username}}</a></td>
        <td class="font-weight-bold">{{$app->name}}</td>
        <td class="font-weight-bold">{{str_limit($app->redirect, 30)}}</td>
        <td class="font-weight-bold">{{$app->revoked ? 'true' : 'false'}}</td>
        <td class="font-weight-bold">{{now()->parse($app->created_at)->diffForHumans()}}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection