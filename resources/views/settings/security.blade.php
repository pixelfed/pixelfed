@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">{{ __('Security')}}</h3>
  </div>
  <hr>

  <section class="pt-4">

    @include('settings.security.log-panel')
    
    @include('settings.security.device-panel')

    @if(config('pixelfed.account_deletion') && !$user->is_admin)
    <h4 class="font-weight-bold pt-3">{{ __('Danger Zone')}}</h4>
    <div class="mb-4 border rounded border-danger">
      <ul class="list-group mb-0 pb-0">
        <li class="list-group-item border-left-0 border-right-0 py-3 d-flex justify-content-between">
          <div>
            <p class="font-weight-bold mb-1">{{ __('Temporarily Disable Account')}}</p>
            <p class="mb-0 small">{{ __('Disable your account to hide your posts until next log in.')}}</p>
          </div>
          <div>
            <a class="btn btn-outline-danger font-weight-bold py-1" href="{{route('settings.remove.temporary')}}">{{ __('Disable')}}</a>
          </div>
        </li>
        <li class="list-group-item border-left-0 border-right-0 py-3 d-flex justify-content-between">
          <div>
            <p class="font-weight-bold mb-1">{{ __('Delete this Account')}}</p>
            <p class="mb-0 small">{{ __('Once you delete your account, there is no going back. Please be certain.')}}</p>
          </div>
          <div>
            <a class="btn btn-outline-danger font-weight-bold py-1" href="{{route('settings.remove.permanent')}}">{{ __('Delete')}}</a>
          </div>
        </li>
      </ul>
    </div>
    @endif
  </section>

@endsection