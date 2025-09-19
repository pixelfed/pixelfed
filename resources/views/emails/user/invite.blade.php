<x-mail::message>
# You've been invited to join {{ config('app.name') }}!

<x-mail::panel>
{{ $invite->message }}

Click the link below to register your account.
</x-mail::panel>

<x-mail::button :url="$invite->url()">
Accept Invite
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}

<small>This email is automatically generated. Please do not reply to this message.</small>
</x-mail::message>
