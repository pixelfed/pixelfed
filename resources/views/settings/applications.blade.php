@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Applications</h3>
  </div>
  <hr>
<passport-authorized-clients></passport-authorized-clients>
<passport-personal-access-tokens></passport-personal-access-tokens>

@endsection

@push('scripts')
<script type="text/javascript">
  new Vue({ 
    el: '#content' 
  });
</script>
@endpush