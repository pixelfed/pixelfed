@component('mail::message')
# New Support Message

<br>

[**{{$contact->user->username}}**]({{$contact->user->url()}}) has sent the following message:

@component('mail::panel')
{{ $contact->message }}
@endcomponent

@component('mail::button', ['url' => $contact->adminUrl(), 'color' => 'primary'])
View Message
@endcomponent

@endcomponent