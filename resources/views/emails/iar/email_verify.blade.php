@component('mail::message')
<div class="otcontainer">

## Verify Your Email Address
        
<p class="ottext">
    Hello,
</p>

<p class="ottext">
    Thank you for signing up to {{config('pixelfed.domain.app')}}!
</p>

<p class="ottext">
    To complete your registration, please enter the following verification code:
</p>

<div class="otcode">
    {{ $code }}
</div>

<p class="ottext">
This code will expire in 4 hours. If you didn't request this verification, please ignore this email.
</p>

<div class="otfooter">
<p>If you're having trouble with the verification code, please contact our <a href="{{route('site.help')}}">support team</a>.</p>
</div>

</div>
@endcomponent
