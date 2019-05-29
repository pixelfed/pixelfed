@extends('layouts.app')

@section('content')
<div>
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
