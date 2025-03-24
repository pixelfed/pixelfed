@extends('site.partial.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">{{__('site.contact-us')}}</h3>
  </div>
  <hr>
  <section>
    @auth
    <p class="lead">
      @if(config('instance.email') && config('instance.contact.enabled'))
        {{__('site.you_can_contact_the_admins')}} {{__('site.by_sending_an_email_to')}} <span class="font-weight-bold">{{config('instance.email')}}</span> {{__('site.or')}} {{__('site.by_using_the_form_below')}}.
      @elseif(config('instance.email') && !config('instance.contact.enabled'))
        {{__('site.you_can_contact_the_admins')}} {{__('site.by_sending_an_email_to')}} <span class="font-weight-bold">{{config('instance.email')}}</span>.
      @elseif(!config('instance.email') && config('instance.contact.enabled'))
       {{__('site.you_can_contact_the_admins')}} {{__('site.by_using_the_form_below')}}.
      @else
       {{__('the_admins_have_not_set_a_contact_email_address')}}
      @endif
    </p>
    @if(config('instance.contact.enabled'))
  	<form method="POST">
      @csrf
  		<div class="form-group">
  			<label for="input1" class="font-weight-bold">{{__('site.Message')}}</label>
  			<textarea class="form-control" id="input1" name="message" rows="6" placeholder="" maxlength="500" required>{{old('message')}}</textarea>
  			<span class="form-text text-muted text-right msg-counter">0/500</span>
  		</div>
		<div class="form-group form-check">
			<input type="checkbox" class="form-check-input" id="input2" name="request_response">
			<label class="form-check-label" for="input2">{{__('site.request_response_from_admins')}}</label>
		</div>
  		<button type="submit" class="btn btn-primary font-weight-bold py-0">{{__('site.Submit')}}</button>
  	</form>
    @endif
    @else
    <p class="lead">
      @if(config('instance.email') && config('instance.contact.enabled'))
        {{__('site.you_can_contact_the_admins')}} {{__('site.by_sending_an_email_to')}} <span class="font-weight-bold">{{config('instance.email')}}</span> {{__('site.or')}} {{__('site.log_in_to_send_a_message')}}.
      @elseif (!config('instance.email') && config('instance.contact.enabled'))
        {{__('the_admins_have_not_set_a_contact_email_address')}}. {{__('site.Please')}} {{__('site.log_in_to_send_a_message')}}.
      @elseif (config('instance.email') && !config('instance.contact.enabled'))
        {{__('site.you_can_contact_the_admins')}} {{__('site.by_sending_an_email_to')}} <span class="font-weight-bold">{{config('instance.email')}}</span>.
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