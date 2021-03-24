@extends('layouts.app')

@section('content')
<div>
  <div class="bg-white py-4">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center">
        <div></div>
        <a href="/account/activity" class="cursor-pointer font-weight-bold text-primary">{{__('Notifications')}}</a>
        @if(request()->user()->profile->is_private)
        <a href="/account/follow-requests" class="cursor-pointer font-weight-bold text-dark">{{__('Follow Requests')}}</a>
        @endif
        <div></div>
      </div>
    </div>
  </div>
  <activity-component></activity-component>
</div>
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/activity.js') }}"></script>
<script type="text/javascript">window.App.boot();</script>
@endpush
