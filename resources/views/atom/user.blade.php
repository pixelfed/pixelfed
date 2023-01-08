<?=
/* Using an echo tag here so the `<? ... ?>` won't get parsed as short tags */
'<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL
?>
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/">
	<id>{{$permalink}}</id>
	<title>{{$profile['username']}} on Pixelfed</title>
	<subtitle type="html">{{$profile['note']}}</subtitle>
	<updated>{{$items[0]['created_at']}}</updated>
	<author>
		<name>{{$profile['username']}}</name>
		<uri>{{$profile['url']}}</uri>
	</author>
	<link rel="alternate" type="text/html" href="{{$profile['url']}}" />
	<link rel="self" type="application/atom+xml" href="{{$permalink}}" />
	@foreach($items as $item)
	<entry>
		<id>{{ $item['url'] }}</id>
		<title>{{ $item['content_text'] ? $item['content_text'] : "No caption" }}</title>
		<updated>{{ $item['created_at'] }}</updated>
		<author>
			<name>{{$profile['username']}}</name>
			<uri>{{$profile['url']}}</uri>
		</author>
		<content type="html">
			<![CDATA[
			<img id="rss_item_{{$loop->iteration}}" src="{{ $item['media_attachments'][0]['url'] }}" alt="{{ $item['media_attachments'][0]['description'] }}">
			<p style="padding:10px;">{{ $item['content'] }}</p>
			]]>
		</content>
		<link rel="alternate" href="{{ $item['url'] }}" />
		<summary type="html">{{ $item['content'] }}</summary>
		<media:content url="{{ $item['media_attachments'][0]['url'] }}" type="image/png" medium="image" />
	</entry>
	@endforeach
</feed>
