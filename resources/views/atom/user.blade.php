<?=
	/* Using an echo tag here so the `<? ... ?>` won't get parsed as short tags */
	'<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL
?>
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/">
  <id>{{$permalink}}</id>
  <title>{{$profile['username']}} on Pixelfed</title>
  <subtitle type="html">{{$profile['note']}}</subtitle>
  <updated>{{$profile['created_at']}}</updated>
  <logo></logo>
  <author>
	<id>{{$profile['url']}}</id>
	<uri>{{$profile['url']}}</uri>
	<name>{{$profile['url']}}</name>
	<summary type="html">{{$profile['note']}}</summary>
	<link rel="alternate" type="text/html" href="{{$profile['url']}}"/>
	<link rel="avatar" type="image/jpeg" media:width="120" media:height="120" href="{{$profile['avatar']}}"/>
  </author>
  <link rel="alternate" type="text/html" href="{{$profile['url']}}"/>
  <link rel="self" type="application/atom+xml" href="{{$permalink}}"/>
@foreach($items as $item)
	<entry>
		<title>{{ strip_tags($item['content']) }}</title>
		<link rel="alternate" href="{{ $item['url'] }}" />
		<id>{{ $item['url'] }}</id>
		<author>
			<name> <![CDATA[{{ $profile['username'] }}]]></name>
		</author>
		<summary type="html">
		<![CDATA[
			<img id="rss_item_{{$loop->iteration}}" src="{{ $item['media_attachments'][0]['url'] }}" alt="{{ $item['media_attachments'][0]['description'] }}">
			<p style="padding:10px;">{{ $item['content'] }}</p>
		  ]]>
		</summary>
		<updated>{{ $item['created_at'] }}</updated>
	</entry>
@endforeach
</feed>
