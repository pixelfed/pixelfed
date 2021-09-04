@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">{{__('settings.dataExport')}}</h3>
  </div>
  <hr>
  <div class="alert alert-primary px-3 h6">{{__('settings.dataExportDiscription')}}</div>
  <ul class="list-group">
  	<li class="list-group-item d-flex justify-content-between align-items-center">
  		<div>
  			<span class="font-weight-bold">{{__('settings.exportFollowing')}}</span>
  		</div>
  		<div>
        <form action="/settings/data-export/following" method="post">
          @csrf
          <button type="submit" class="font-weight-bold btn btn-outline-primary btn-sm">{{__('settings.download')}}</button>
        </form>
  		</div>
  	</li>
 	<li class="list-group-item d-flex justify-content-between align-items-center">
  		<div>
  			<span class="font-weight-bold">{{__('settings.exportFollowers')}}</span>
  		</div>
      <div>
        <form action="/settings/data-export/followers" method="post">
          @csrf
          <button type="submit" class="font-weight-bold btn btn-outline-primary btn-sm">{{__('settings.download')}}</button>
        </form>
      </div>
  	</li>
 	<li class="list-group-item d-flex justify-content-between align-items-center">
  		<div>
  			<span class="font-weight-bold">{{__('settings.exportStatuses')}}</span>
  		</div>
  		<div>
        <form action="/settings/data-export/statuses" method="post" class="d-inline">
          @csrf
          <input type="hidden" name="type" value="ap">
          <button type="submit" class="font-weight-bold btn btn-outline-primary btn-sm">{{__('settings.download')}}</button>
        </form>
        {{-- <form action="/settings/data-export/statuses" method="post" class="d-inline">
          @csrf
          <input type="hidden" name="type" value="api">
          <button type="submit" class="font-weight-bold btn btn-outline-primary btn-sm">api.json</button>
        </form> --}}
      </div>
    </li>
  <li class="list-group-item d-flex justify-content-between align-items-center">
      <div>
        <span class="font-weight-bold">{{__('settings.muteBlockList')}}</span>
      </div>
      <div>
        <form action="/settings/data-export/mute-block-list" method="post">
          @csrf
          <button type="submit" class="font-weight-bold btn btn-outline-primary btn-sm">{{__('settings.download')}}</button>
        </form>
      </div>
    </li>
  <li class="list-group-item d-flex justify-content-between align-items-center">
      <div>
        <span class="font-weight-bold">{{__('settings.account')}}</span>
      </div>
      <div>
  			<form action="/settings/data-export/account" method="post">
          @csrf
          <button type="submit" class="font-weight-bold btn btn-outline-primary btn-sm">{{__('settings.download')}}</button>
        </form>
  		</div>
  	</li>
  </ul>

@endsection