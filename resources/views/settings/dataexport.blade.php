@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">{{__('settings.export.data_export')}}</h3>
  </div>
  <hr>
  <div class="alert alert-primary px-3 h6">{{__('settings.export.we_generate_data_exports_once_per_hour_and_they_may_etc')}}</div>
  <ul class="list-group">
  	<li class="list-group-item d-flex justify-content-between align-items-center">
  		<div>
  			<span class="font-weight-bold">{{__('settings.relationships.following')}}</span>
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
  			<span class="font-weight-bold">{{__('settings.relationships.followers')}}</span>
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
  			<span class="font-weight-bold">{{__('settings.export.statuses')}}</span>
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
        <span class="font-weight-bold">{{__('settings.export.mute_block_lists')}}</span>
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