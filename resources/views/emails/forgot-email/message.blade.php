@component('mail::message')
Hello,

You recently requested to know the email address associated with your username [**{{'@' . $user->username}}**]({{$user->url()}}) on [**{{config('pixelfed.domain.app')}}**]({{config('app.url')}}).

We're here to assist! Simply tap on the Login button below.

<x-mail::button :url="url('/login?email=' . urlencode($user->email))" color="success">
Login to my <strong>{{'@' . $user->username}}</strong> account
</x-mail::button>

----
<br>

The email address linked to your username is:
<x-mail::panel>
<p>
<strong>{{$user->email}}</strong>
</p>
</x-mail::panel>

You can use this email address to log in to your account.

<small>If needed, you can [reset your password]({{ route('password.request')}}). For security reasons, we recommend keeping your account information, including your email address, updated and secure. If you did not make this request or if you have any other questions or concerns, please feel free to [contact our support team]({{route('site.contact')}}).</small>

Thank you for being a part of our community!

Best regards,<br>
<a href="{{ config('app.url') }}"><strong>{{ config('pixelfed.domain.app') }}</strong></a>
<br>
<hr>
<p style="font-size:10pt;">This is an automated message, please be aware that replies to this email cannot be monitored or responded to.</p>
@endcomponent
