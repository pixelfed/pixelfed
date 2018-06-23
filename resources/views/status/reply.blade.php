@extends('layouts.app')

@section('content')

<div class="container">
  <div class="col-12 col-md-8 offset-md-2 mt-4">
    @php($item = $status->parent())
    @php($showSingleComment = true)
    @include('status.template')
  </div>
</div>

@endsection

@push('scripts')
<script type="text/javascript">
  $(document).ready(function() {
    $('.reactions').hide();
    $('.more-comments').hide();
    $('.card-footer').hide();
  }); 
</script>
@endpush