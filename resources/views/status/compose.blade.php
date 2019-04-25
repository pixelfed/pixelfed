@extends('layouts.app')

@section('content')

<div class="container mt-5">
	<div class="row">
		<div class="col-12 col-md-6 offset-md-3">
			@include('timeline.partial.new-form')
		</div>
	</div>
</div>

@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/compose.js') }}"></script>
<script type="text/javascript">
$(document).ready(function() {
  new Vue({ 
    el: '#content'
  });

  $('.metro-classic-compose textarea[name="caption"]').on('click', function(e) {
  	  let el = $(this);
  	  el.attr('rows', 4);
  });
  $('.metro-classic-compose textarea[name="caption"]').on('change keyup paste', function(e) {
  	  let el = $(this);
      let len = el.val().length;
      let limit = el.attr('data-limit');

      let res = len;

      if(len > limit) {
      	res = '<span class="text-danger">' + (limit - len) + '</span>';
      } else {
      	res = '<span>' + len + '</span>';
      }
      $('.metro-classic-compose .caption-counter').html(res);
  })
});
</script>
@endpush