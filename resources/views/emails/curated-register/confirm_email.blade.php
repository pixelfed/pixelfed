@component('mail::message')
# Action Needed: Confirm Your Email to Activate Your Pixelfed Account

Hello **{{'@'.$verify->username}}**,

Please confirm your email address so we can process your new registration application.

<x-mail::button :url="$verify->emailConfirmUrl()" color="success">
<strong>Confirm Email Address</strong>
</x-mail::button>


<p style="font-size:10pt;">If you did not create this account, please disregard this email. This link expires in 7 days.</p>
<br>

Thanks,<br>
<a href="{{ config('app.url') }}">{{ config('pixelfed.domain.app') }}</a>
<br>
<hr>
<p style="font-size:10pt;">This is an automated message, please be aware that replies to this email cannot be monitored or responded to.</p>
@endcomponent
