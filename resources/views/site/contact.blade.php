@extends('site.partial.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Contact</h3>
  </div>
  <hr>
  <section>
    @auth
  	<form method="POST">
      @csrf
  		<div class="form-group">
  			<label for="input1" class="font-weight-bold">Message</label>
  			<textarea class="form-control" id="input1" name="message" rows="6" placeholder=""></textarea>
  			<span class="form-text text-muted text-right msg-counter">0/500</span>
  		</div>
		<div class="form-group form-check">
			<input type="checkbox" class="form-check-input" id="input2" name="request_response">
			<label class="form-check-label" for="input2">Request response from admins</label>
		</div>
  		<button type="submit" class="btn btn-primary font-weight-bold py-0">Submit</button>
  	</form>
    @else
    <p class="lead">
      @if(filter_var(config('instance.email'), FILTER_VALIDATE_EMAIL) == true)
        You can contact the admins by sending an email to {{config('instance.email')}}.
      @else
        The admins have not listed any public email. Please log in to send a message.
      @endif
    </p>
    @endauth
  </section>
@endsection

@auth
@push('styles')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('scripts')
<script type="text/javascript">
  $('#input1').on('keyup change paste', function(el) {
    let len = el.target.value.length;
    $('.msg-counter').text(len + '/500');
  });
</script>
@endpush
@endauth