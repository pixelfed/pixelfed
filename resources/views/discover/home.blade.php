@extends('layouts.app')

@section('content')
<div class="container mt-5 pt-3">
  <section>
    <discover-component></discover-component>
  </section>
</div>
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/discover.js') }}"></script>
<script type="text/javascript">
$(document).ready(function(){new Vue({el: '#content'});});
</script>
@endpush