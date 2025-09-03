<h2><span style="opacity:0.5;">Let's begin setting up your account on</span> <strong style="opacity: 1">{{ config('pixelfed.domain.app') }}</strong></h2>
<form method="post">
    @csrf
    <input type="hidden" name="step" value="2">
    <div class="my-5 details-form">
        <div class="details-form-field">
            <label class="text-muted small font-weight-bold mb-0">Username</label>
            <div class="input-group">
                <input
                    type="text"
                    class="form-control form-control-lg bg-dark border-dark text-white"
                    placeholder="username"
                    aria-label="Your username"
                    aria-describedby="username-addon"
                    maxlength="30"
                    required
                    name="username"
                    value="{{ request()->session()->get('cur-reg.form-username') }}">
                <div class="input-group-append">
                    <span class="input-group-text bg-dark border-dark text-muted font-weight-bold" id="username-addon">&commat;{{ config('pixelfed.domain.app') }}</span>
                </div>
            </div>
            <p class="help-text small text-muted mb-0">You can use letters, numbers, and underscores with a max length of 30 chars.</p>
        </div>

        <div class="details-form-field">
            <label class="text-muted small font-weight-bold mb-0">Email</label>
            <input
                type="text"
                class="form-control form-control-lg bg-dark border-dark text-white"
                placeholder="Your email address"
                name="email"
                value="{{ request()->session()->get('cur-reg.form-email') }}"
                required>
        </div>

        <div class="details-form-field">
            <label class="text-muted small font-weight-bold mb-0">Password</label>
            <input
                type="password"
                autocomplete="new-password"
                minlength="6"
                class="form-control form-control-lg bg-dark border-dark text-white"
                placeholder="Password"
                name="password"
                required>
        </div>

        <div class="details-form-field">
            <label class="text-muted small font-weight-bold mb-0">Password Confirm</label>
            <input
                type="password"
                autocomplete="new-password"
                minlength="6"
                class="form-control form-control-lg bg-dark border-dark text-white"
                placeholder="Confirm Password"
                name="password_confirmation"
                required>
        </div>
        <div class="border-top border-dark mt-3 pt-4">
            <p class="lead">
                Our moderators manually review sign-ups. To assist in the processing of your registration, please provide some information about yourself and explain why you wish to create an account on {{ config('pixelfed.domain.app') }}.
            </p>
        </div>
        <div class="details-form-field">
            <label class="text-muted small font-weight-bold mb-0">About yourself and why you'd like to join</label>
            <textarea
                class="form-control form-control-lg bg-dark text-white border-dark"
                rows="4"
                name="reason"
                maxlength="1000"
                id="reason"
                placeholder="Briefly explain why you'd like to join and optionally provide links to other accounts to help admins process your request"
                required>{{ request()->session()->get('cur-reg.form-reason') }}</textarea>
            <div class="help-text small text-muted float-right mt-1 font-weight-bold">
                <span id="charCount" class="text-white">0</span>/<span>1000</span>
            </div>
        </div>
        <div class="border-top border-dark mt-3 pt-4">
            <div class="details-form-field">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="agree" name="agree" required>
                    <label class="custom-control-label text-muted" for="agree">I have read and agreed to our <a href="/site/terms" target="_blank" class="text-white">Terms of Use</a> and <a href="/site/privacy" target="_blank" class="text-white">Privacy Policy</a>.</label>
                </div>
            </div>
        </div>
        <div class="mt-3 pt-4">
            <button class="btn btn-primary rounded-pill font-weight-bold btn-block">Proceed</button>
        </div>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var textInput = document.getElementById('reason');
    var charCount = document.getElementById('charCount');
    var currentLength = textInput.value.length;
    charCount.textContent = currentLength;

    textInput.addEventListener('input', function () {
        var currentLength = textInput.value.length;
        charCount.textContent = currentLength;
    });
});
</script>
@endpush

@push('styles')
<style type="text/css">
.details-form {
    display: flex;
    gap: 1rem;
    flex-direction: column;
}
</style>
@endpush
