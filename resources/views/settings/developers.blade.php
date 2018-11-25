@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Developers</h3>
  </div>
  <hr>
  <passport-clients></passport-clients>

@endsection

@push('scripts')
<script type="text/javascript">
  new Vue({ 
    el: '#content' 
  });
</script>
@endpush