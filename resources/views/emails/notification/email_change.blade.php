@component('mail::message')
# Account Email Changed


@component('mail::panel')
<p>The email associated to your account has been changed.</p>
@endcomponent

<small>If you did not make this change and believe your Pixelfed account has been compromised, please contact the instance admin.</small>

<br>

Thanks,<br>
{{ config('pixelfed.domain.app') }}
@endcomponent
