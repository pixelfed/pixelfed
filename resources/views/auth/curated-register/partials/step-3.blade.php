<h2><span style="opacity:0.5;">Confirm Your Email</h2>
@if(isset($verifiedEmail))
<div class="alert alert-success bg-success border-success p-4 text-center mt-5">
    <p class="text-center text-white mb-4"><i class="far fa-envelope-open fa-4x"></i></p>
    <p class="lead font-weight-bold text-white mb-0">Please check your email inbox, we sent an email confirmation with a link that you need to visit.</p>
</div>
@else
<p class="lead">Please confirm your email address is correct, we will send a verification e-mail with a special verification link that you need to visit before proceeding.</p>
<form method="post">
    @csrf
    <input type="hidden" name="step" value="3">
    <div class="details-form-field">
          <input
            type="text"
            class="form-control form-control-lg bg-dark border-dark text-white"
            placeholder="Your email address"
            name="email"
            value="{{ request()->session()->get('cur-reg.form-email') }}"
            required>
    </div>
    @if(config('instance.curated_registration.captcha_enabled'))
    <div class="d-flex justify-content-center my-3">
        {!! Captcha::display() !!}
    </div>
    @endif
    <div class="mt-3 pt-4">
        <button class="btn btn-primary rounded-pill font-weight-bold btn-block">My email is correct</button>
    </div>
</form>
@endif
