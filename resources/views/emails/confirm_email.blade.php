@component('mail::message')
# Email Confirmation

Hello <b>&commat;{{$verify->user->username}}</b>, please confirm your email address.

If you did not create this account, please disregard this email.

@component('mail::button', ['url' => $verify->url()])
Confirm Email
@endcomponent

<p>This link expires after 24 hours.</p>
<br>

Thanks,<br>
<a href="{{ config('app.url') }}">{{ config('pixelfed.domain.app') }}</a>
@endcomponent
