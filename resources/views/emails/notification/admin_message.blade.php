@component('mail::message')
# Message from {{ config('pixelfed.domain.app') }}:


@component('mail::panel')
{{$msg}}
@endcomponent


<br>

Regards,<br>
{{ config('pixelfed.domain.app') }}

@component('mail::subcopy')
Please do not reply to this email, this address is not monitored.
@endcomponent

@endcomponent

