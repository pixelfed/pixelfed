@component('mail::message')
# Action Needed: Additional information requested

Hello **{{'@'.$verify->username}}**

To help us process your registration application, we require more information.

Our onboarding team have requested the following details:

@component('mail::panel')
<p style="white-space: pre-wrap;">{!! $activity->message !!}</p>
@endcomponent
<x-mail::button :url="$activity->emailReplyUrl()" color="success">
<strong>Reply with your response</strong>
</x-mail::button>

<p style="font-size:10pt;">Please respond promptly, your application will be automatically removed 7 days after your last interaction.</p>
<br>

Thanks,<br>
<a href="{{ config('app.url') }}">{{ config('pixelfed.domain.app') }}</a>
@endcomponent
