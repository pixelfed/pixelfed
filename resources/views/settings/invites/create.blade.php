@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Send Invite</h3>
    <p class="lead">Invite friends or family to join you on <span class="font-weight-bold">{{config('pixelfed.domain.app')}}</span></p>
  </div>
  <hr>

  @if(config('pixelfed.user_invites.limit.daily') != 0)
  <div class="alert alert-warning">
    <div class="font-weight-bold">Warning</div>
    <p class="mb-0">You may only send {{config('pixelfed.user_invites.limit.daily')}} invite(s) per day.</p>
  </div>
  @endif

  <form method="post">
    @csrf
    <div class="form-group">
      <label>Email address</label>
      <input type="email" class="form-control" name="email" placeholder="friend@example.org" autocomplete="off">
    </div>
    <div class="form-group">
      <label>Message</label>
      <textarea class="form-control" name="message" placeholder="Add an optional message" rows="2"></textarea>
      <p class="help-text mb-0 text-right small text-muted"><span class="message-count">0</span>/<span class="message-limit">500</span></p>
    </div>
    <div class="form-group form-check">
      <input type="checkbox" class="form-check-input" id="tos" name="tos">
      <label class="form-check-label font-weight-bold small" for="tos">I confirm this invitation is not in violation of the <a href="{{route('site.terms')}}">Terms of Service</a> and <a href="{{route('site.privacy')}}">Privacy Policy</a>.</label>
    </div>
    <hr>
    <p class="float-right">
      <button type="submit" class="btn btn-primary font-weight-bold py-0 form-submit">Send Invite</button>
    </p>
  </form>
@endsection

@push('scripts')
<script type="text/javascript">
  
  $('textarea[name="message"]').on('change keyup paste', function(e) {
    let el = $(this);
    let len = el.val().length;
    let limit = $('.message-limit').text();

    if(len > 100) {
      el.attr('rows', '4');
    }

    if(len > limit) {
      let diff = len - limit;
      $('.message-count').addClass('text-danger').text('-'+diff);
      $('.form-submit').attr('disabled','');
    } else {
      $('.message-count').removeClass('text-danger').text(len);
      $('.form-submit').removeAttr('disabled');
    }

  });

</script>
@endpush