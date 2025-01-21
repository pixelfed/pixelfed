@component('mail::message')
# [#{{$verify->id}}] New Curated Onboarding Application

Hello admin,

**Please review this new onboarding application.**

<x-mail::panel>
<p>
<small>
Username: <strong>{{ $verify->username }}</strong>
</small>
<br>
<small>
Email: <strong>{{ $verify->email }}</strong>
</small>
</p>

<hr>

<small><strong>*The user provided the following reason to join:*</strong></small>
<p style="font-size:9pt;">{!!str_limit(nl2br($verify->reason_to_join), 300)!!}</p>
</x-mail::panel>

<x-mail::button :url="$verify->adminReviewUrl()" color="success">
<strong>Review Onboarding Application</strong>
</x-mail::button>

Thanks,<br>
<a href="{{ config('app.url') }}">{{ config('pixelfed.domain.app') }}</a>
<br>
<hr>
<p style="font-size:10pt;">This is an automated message, please be aware that replies to this email cannot be monitored or responded to.</p>
@endcomponent
