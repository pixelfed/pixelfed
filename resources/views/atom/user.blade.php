<?=
    /* Using an echo tag here so the `<? ... ?>` won't get parsed as short tags */
    '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL
?>
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/">
  <id>{{$profile->permalink('.atom')}}</id>
  <title>{{$profile->username}} on Pixelfed</title>
  <subtitle>{{$profile->bio}}</subtitle>
  <updated>{{$profile->updated_at->toAtomString()}}</updated>
  <logo></logo>
  <author>
    <id>{{$profile->permalink()}}</id>
    <uri>{{$profile->permalink()}}</uri>
    <name>{{$profile->permalink()}}</name>
    <summary type="html">{{$profile->bio}}</summary>
    <link rel="alternate" type="text/html" href="{{$profile->url()}}"/>
    <link rel="avatar" type="image/jpeg" media:width="120" media:height="120" href="{{$profile->avatarUrl()}}"/>
  </author>
  <link rel="alternate" type="text/html" href="{{$profile->url()}}"/>
  <link rel="self" type="application/atom+xml" href="{{$profile->permalink('.atom')}}"/>
@foreach($items as $item)
    <entry>
        <title>{{ $item->caption }}</title>
        <link rel="alternate" href="{{ $item->url() }}" />
        <id>{{ $item->url() }}</id>
        <author>
            <name> <![CDATA[{{ $item->profile->username }}]]></name>
        </author>
        <summary type="html">
        <![CDATA[
            <img id="rss_item_{{$loop->iteration}}" src="{{ $item->thumb() }}">
            <p style="padding:10px;">{{ $item->caption }}</p>
          ]]>
        </summary>
        <updated>{{ $item->updated_at->toAtomString() }}</updated>
    </entry>
@endforeach
</feed>
