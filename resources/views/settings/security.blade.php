@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Security</h3>
  </div>
  <hr>

  <section class="pt-4">
    <div class="mb-4 pb-4">
      <div class="d-flex justify-content-between align-items-center">
        <h4 class="font-weight-bold mb-0">Two-factor authentication</h4>
        @if($user->{'2fa_enabled'})
        <a class="btn btn-success btn-sm font-weight-bold" href="#">Enabled</a>
        @endif
      </div>
      <hr>
      @if($user->{'2fa_enabled'})
      @include('settings.security.2fa.partial.edit-panel')
      @else
      @include('settings.security.2fa.partial.disabled-panel')
      @endif
    </div>

    @include('settings.security.log-panel')
    
    @include('settings.security.device-panel')

    @if(config('pixelfed.account_deletion') && !$user->is_admin)
    <h4 class="font-weight-bold pt-3">Danger Zone</h4>
    <div class="mb-4 border rounded border-danger">
      <ul class="list-group mb-0 pb-0">
        <li class="list-group-item border-left-0 border-right-0 py-3 d-flex justify-content-between">
          <div>
            <p class="font-weight-bold mb-1">Temporarily Disable Account</p>
            <p class="mb-0 small">Disable your account to hide your posts until next log in.</p>
          </div>
          <div>
            <a class="btn btn-outline-danger font-weight-bold py-1" href="{{route('settings.remove.temporary')}}">Disable</a>
          </div>
        </li>
        <li class="list-group-item border-left-0 border-right-0 py-3 d-flex justify-content-between">
          <div>
            <p class="font-weight-bold mb-1">Delete this Account</p>
            <p class="mb-0 small">Once you delete your account, there is no going back. Please be certain.</p>
          </div>
          <div>
            <a class="btn btn-outline-danger font-weight-bold py-1" href="{{route('settings.remove.permanent')}}">Delete</a>
          </div>
        </li>
      </ul>
    </div>
    @endif
  </section>

@endsection