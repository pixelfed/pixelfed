@extends('layouts.app')

@section('content')
<div>
  <div class="bg-white py-4">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center">
        <div></div>
        <a href="/account/activity" class="cursor-pointer font-weight-bold text-primary">Notifications</a>
        <a href="/account/follow-requests" class="cursor-pointer font-weight-bold text-dark">Follow Requests</a>
        <div></div>
      </div>
    </div>
  </div>
  <activity-component></activity-component>
</div>
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/compose.js') }}"></script>
<script type="text/javascript" src="{{ mix('js/activity.js') }}"></script>
<script type="text/javascript">
  new Vue({
    el: '#content'
  });
</script>
@endpush
