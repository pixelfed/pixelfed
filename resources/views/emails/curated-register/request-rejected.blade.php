@component('mail::message')
Hello **{{'@'.$verify->username}}**,

We appreciate the time you took to apply for an account on {{ config('pixelfed.domain.app') }}.

Unfortunately, after reviewing your [application]({{route('help.curated-onboarding')}}), we have decided not to proceed with the activation of your account.

This decision is made to ensure the best experience for all members of our community. We encourage you to review our [guidelines]({{route('help.community-guidelines')}}) and consider applying again in the future.

We appreciate your understanding. If you believe this decision was made in error, or if you have any questions, please don’t hesitate to [contact us]({{route('site.contact')}}).

<br>

Thanks,<br>
<a href="{{ config('app.url') }}">{{ config('pixelfed.domain.app') }}</a>
<br>
<hr>
<p style="font-size:10pt;">This is an automated message, please be aware that replies to this email cannot be monitored or responded to.</p>
@endcomponent
