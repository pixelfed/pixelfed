@component('mail::message')
# Account Password Changed


@component('mail::panel')
<p>The password for your account has been changed.</p>
@endcomponent

<small>If you did not make this change and believe your Pixelfed account has been compromised, please change your password immediately or contact the instance admin if you're locked out of your account.</small>

<br>

Thanks,<br>
{{ config('pixelfed.domain.app') }}
@endcomponent
