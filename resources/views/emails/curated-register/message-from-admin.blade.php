@component('mail::message')
# New Message from {{config('pixelfed.domain.app')}}

Hello,

You recently applied to join our Pixelfed community using the &commat;**{{ $verify->username }}** username.

The admins have a message for you:

<x-mail::panel>
<p style="white-space: pre-wrap;">{{ $verify->message }}</p>
</x-mail::panel>

Please do not respond to this email, any replies will not be seen by our admin team.

Thanks,<br>
<a href="{{ config('app.url') }}">{{ config('pixelfed.domain.app') }}</a>
<br>
<hr>
<p style="font-size:10pt;">This is an automated message on behalf of our admin team, please be aware that replies to this email cannot be monitored or responded to.</p>
@endcomponent
