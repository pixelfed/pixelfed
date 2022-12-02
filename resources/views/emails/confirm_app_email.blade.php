<x-mail::message>
# Complete Account Registration

Hello **{{'@'.$verify->user->username}}**,

You are moments away from finishing your new account registration!

@component('mail::button', ['url' => $appUrl])
Complete Account Registration
@endcomponent

<p style="color: #d6d3d1;font-size: 10pt">Make sure you click on the button from your mobile device, opening the link using a desktop browser won't work.</p>
<br>
<p>If you did not create this account, please disregard this email.</p>

Thanks,<br>
<a href="{{ config('app.url') }}">{{ config('pixelfed.domain.app') }}</a>
</x-mail::message>
