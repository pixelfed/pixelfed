@extends('layouts.app')

@section('content')

<collection-compose collection-id="{{$collection->id}}" profile-id="{{Auth::user()->profile_id}}"></collection-compose>

@endsection

@push('styles')
<style type="text/css">
</style>
@endpush

@push('scripts')
<script type="text/javascript" src="{{ mix('js/collectioncompose.js') }}"></script>
<script type="text/javascript" src="{{ mix('js/compose.js') }}"></script>
<script type="text/javascript">
$(document).ready(function() {
  new Vue({ 
    el: '#content'
  });
});
</script>
@endpush