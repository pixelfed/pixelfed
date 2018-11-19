@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Data Export</h3>
  </div>
  <hr>
  <ul class="list-group">
  	<li class="list-group-item d-flex justify-content-between align-items-center">
  		<div>
  			<span class="font-weight-bold">Following</span>
  		</div>
  		<div>
        <form action="/settings/data-export/following" method="post">
          @csrf
          <button type="submit" class="font-weight-bold btn btn-outline-primary btn-sm">Download</button>
        </form>
  		</div>
  	</li>
 	<li class="list-group-item d-flex justify-content-between align-items-center">
  		<div>
  			<span class="font-weight-bold">Followers</span>
  		</div>
      <div>
        <form action="/settings/data-export/followers" method="post">
          @csrf
          <button type="submit" class="font-weight-bold btn btn-outline-primary btn-sm">Download</button>
        </form>
      </div>
  	</li>
 	<li class="list-group-item d-flex justify-content-between align-items-center">
  		<div>
  			<span class="font-weight-bold">Statuses</span>
  		</div>
  		<div>
        <span class="small text-muted">Coming Soon</span>
      </div>
    </li>
  <li class="list-group-item d-flex justify-content-between align-items-center">
      <div>
        <span class="font-weight-bold">Mute/Block List</span>
      </div>
      <div>
        <form action="/settings/data-export/mute-block-list" method="post">
          @csrf
          <button type="submit" class="font-weight-bold btn btn-outline-primary btn-sm">Download</button>
        </form>
      </div>
    </li>
  <li class="list-group-item d-flex justify-content-between align-items-center">
      <div>
        <span class="font-weight-bold">Account</span>
      </div>
      <div>
  			<span class="small text-muted">Coming Soon</span>
  		</div>
  	</li>
  </ul>

@endsection