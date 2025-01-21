<x-mail::message>
Hello **&commat;{{$contact->user->username}}**,

You contacted the admin team of {{config('pixelfed.domain.app')}} with the following inquiry:

<x-mail::panel>
<i>{{str_limit($contact->message, 80)}}</i>
</x-mail::panel>

<x-mail::button :url="$url" color="primary">
    View Admin Response
</x-mail::button>

<small>
or copy and paste the following url: <a href="{{$url}}">{{$url}}</a>
</small>
<br>
<br>
<br>
<small>
Thanks,<br>
The {{ ucfirst(config('pixelfed.domain.app')) }} Admin Team
</small>
</x-mail::message>
