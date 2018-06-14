@component('mail::message')
# Email Confirmation

Please confirm your email address.

@component('mail::button', ['url' => $verify->url()])
Confirm Email
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
