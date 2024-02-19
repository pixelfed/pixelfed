<p class="mt-5 lead text-center font-weight-bold">Please verify your email address</p>
<form method="post">
    @csrf
    <input type="hidden" name="action" value="email">
    <div class="form-group">
        <input
            type="email"
            class="form-control form-control-lg bg-dark border-dark text-white"
            name="email"
            placeholder="Your email address"
            required />
    </div>
    @if(config('instance.curated_registration.captcha_enabled'))
    <div class="d-flex justify-content-center my-3">
        {!! Captcha::display() !!}
    </div>
    @endif
    <div class="d-flex justify-content-center">
        <button class="btn btn-primary font-weight-bold rounded-pill px-5">Verify</button>
    </div>
</form>
<hr class="border-dark">
<p class="text-muted small text-center">For additional information, please see our <a href="{{ route('help.curated-onboarding') }}" style="font-weight: 600;">Curated Onboarding</a> Help Center page.</p>
