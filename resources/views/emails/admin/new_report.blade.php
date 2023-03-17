<x-mail::message>
# {{ $title }}

## {!! $message !!}

<x-mail::button :url="$url">
View Report
</x-mail::button>

@if($object_type === 'Status' && $reported_status)
<x-mail::panel>
<p style="font-size: 13px; color: #cccccc; text-align: center; font-weight: bold;margin-bottom: 10px;">Reported Status</p>
<div style="display: flex; align-items: center;gap: 10px;">
@if(
	isset($reported_status['media_attachments']) &&
	isset($reported_status['pf_type']) &&
	count($reported_status['media_attachments']) &&
	in_array($reported_status['pf_type'], ['photo', 'photo:album'])
)
<img
	src="{{$reported_status['media_attachments'][0]['url']}}"
	width="100"
	height="100"
	alt="Media preview"
	style="object-fit: cover; border: 1px solid #cccccc; border-radius: 10px; margin-bottom: 10px;"
	onerror="this.src='{{url('/storage/no-preview.png')}}';this.onerror=null;" />
@endif
@if(isset($reported_status['content']))
<div style="font-size: 12px !important;">{{ strip_tags(str_replace(["\n", "\r", "\r\n"], ' ', $reported_status['content'])) }}</div>
@endif
</div>
<div style="display: flex; align-items: center; justify-content: space-between;margin-top:10px;">
<a style="font-size: 11px !important;font-weight: bold;text-decoration: none;" href="{{ url('/i/web/post/' . $reported_status['id'])}}">
	View status
</a>
<p style="font-size: 11px !important;font-weight: bold;">
	Posted {{ now()->parse($reported_status['created_at'])->diffForHumans() }}
</p>
</div>
</x-mail::panel>
@endif

@if($reported && isset($reported['id']))
<x-mail::panel>
<p style="font-size: 13px; color: #cccccc; text-align: center; font-weight: bold;margin-bottom: 10px;">Reported Account</p>

<div style="display: flex; align-items: flex-start;gap: 10px;">

<img
	src="{{$reported['avatar']}}"
	width="50"
	height="50"
	alt="Avatar"
	style="border-radius: 10px;min-width: 50px;flex-grow: 1;"
	onerror="this.src='{{url('/storage/avatars/default.jpg')}}';this.onerror=null;" />
<div>

<p style="margin-bottom: 0;">
<a href="{{ url('/i/web/profile/' . $reported['id']) }}" style="text-decoration: none;font-weight: bold">{{ $reported['username'] }}</a>
</p>

<p style="margin-bottom: 5px;font-size: 10px;opacity: 0.5;">
{{ strip_tags(str_replace(["\n", "\r", "\r\n"], ' ', $reported['note'])) }}
</p>

<div style="display: flex; align-items: center; gap: 5px;">
<p style="font-size: 10px;margin-bottom: 0;">{{ $reported['statuses_count'] }} posts</p>
<p style="font-size: 10px;margin-bottom: 0;">·</p>
<p style="font-size: 10px;margin-bottom: 0;">{{ $reported['followers_count'] }} followers</p>
<p style="font-size: 10px;margin-bottom: 0;">·</p>
<p style="font-size: 10px;margin-bottom: 0;">{{ $reported['following_count'] }} following</p>
<p style="font-size: 10px;margin-bottom: 0;">·</p>
<p style="font-size: 10px;margin-bottom: 0;">Joined {{ now()->parse($reported['created_at'])->diffForHumans()}}</p>
</div>

</div>
</div>
</x-mail::panel>
@endif

@if($reporter && isset($reporter['id']))
<x-mail::panel>
<p style="font-size: 13px; color: #cccccc; text-align: center; font-weight: bold;margin-bottom: 10px;">Reported By</p>

<div style="display: flex; align-items: flex-start;gap: 10px;">

<img
	src="{{$reporter['avatar']}}"
	width="50"
	height="50"
	alt="Avatar"
	style="border-radius: 10px;min-width: 50px;flex-grow: 1;"
	onerror="this.src='{{url('/storage/avatars/default.jpg')}}';this.onerror=null;" />
<div>

<p style="margin-bottom: 0;"><a href="{{ url('/i/web/profile/' . $reporter['id']) }}" style="text-decoration: none;font-weight: bold">{{ $reporter['username'] }}</a>
</p>

<p style="margin-bottom: 5px;font-size: 10px;opacity: 0.5;">
{{ strip_tags(str_replace(["\n", "\r", "\r\n"], ' ', $reporter['note'])) }}
</p>

<div style="display: flex; align-items: center; gap: 5px;">
<p style="font-size: 10px;margin-bottom: 0;">{{ $reporter['statuses_count'] }} posts</p>
<p style="font-size: 10px;margin-bottom: 0;">·</p>
<p style="font-size: 10px;margin-bottom: 0;">{{ $reporter['followers_count'] }} followers</p>
<p style="font-size: 10px;margin-bottom: 0;">·</p>
<p style="font-size: 10px;margin-bottom: 0;">{{ $reporter['following_count'] }} following</p>
<p style="font-size: 10px;margin-bottom: 0;">·</p>
<p style="font-size: 10px;margin-bottom: 0;">Joined {{ now()->parse($reporter['created_at'])->diffForHumans()}}</p>
</div>
</div>
</div>
</x-mail::panel>
@endif

<p style="font-size: 12px;color: #cccccc;text-align: center;">
This is an automated email that is intended for administrators of {{ config('pixelfed.domain.app')}}.<br />
If you received this email by mistake, kindly disregard and delete this email.
</p>

</x-mail::message>
