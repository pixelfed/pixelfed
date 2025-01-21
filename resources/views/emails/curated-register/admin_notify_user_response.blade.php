@component('mail::message')
# New Curated Onboarding Response ({{ '#' . $activity->id}})

Hello,

You have a new response from a curated onboarding application from **{{$activity->application->email}}**.

<x-mail::panel>
<p style="white-space: pre-wrap;">{!! $activity->message !!}</p>
</x-mail::panel>

<x-mail::button :url="$activity->adminReviewUrl()" color="success">
<strong>Review Onboarding Response</strong>
</x-mail::button>

Thanks,<br>
<a href="{{ config('app.url') }}">{{ config('pixelfed.domain.app') }}</a>
<br>
<hr>
<p style="font-size:10pt;">This is an automated message, please be aware that replies to this email cannot be monitored or responded to.</p>
@endcomponent
