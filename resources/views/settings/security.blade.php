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

    @include('settings.security.2fa.partial.log-panel')
  </section>

@endsection