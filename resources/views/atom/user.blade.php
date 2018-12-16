<?=
    /* Using an echo tag here so the `<? ... ?>` won't get parsed as short tags */
    '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL
?>
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:thr="http://purl.org/syndication/thread/1.0" xmlns:activity="http://activitystrea.ms/spec/1.0/" xmlns:poco="http://portablecontacts.net/spec/1.0" xmlns:media="http://purl.org/syndication/atommedia" xmlns:ostatus="http://ostatus.org/schema/1.0" xmlns:mastodon="http://mastodon.social/schema/1.0">
  <id>{{$profile->permalink('.atom')}}</id>
  <title>{{$profile->username}}</title>
  <subtitle>{{$profile->bio}}</subtitle>
  <updated>{{$profile->updated_at->toAtomString()}}</updated>
  <logo></logo>
  <author>
    <id>{{$profile->permalink()}}</id>
    <activity:object-type>http://activitystrea.ms/schema/1.0/person</activity:object-type>
    <uri>{{$profile->permalink()}}</uri>
    <name>{{$profile->permalink()}}</name>
    <email>{{$profile->emailUrl()}}</email>
    <summary type="html">{{$profile->bio}}</summary>
    <link rel="alternate" type="text/html" href="{{$profile->url()}}"/>
    <link rel="avatar" type="image/jpeg" media:width="120" media:height="120" href="{{$profile->avatarUrl()}}"/>
    <poco:preferredUsername>{{$profile->username}}</poco:preferredUsername>
    <poco:displayName>{{$profile->name}}</poco:displayName>
    <poco:note>{{$profile->bio}}</poco:note>
    <mastodon:scope>public</mastodon:scope>
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
            {{ $item->caption }}
        </summary>
        <updated>{{ $item->updated_at->toAtomString() }}</updated>
    </entry>
@endforeach
</feed>
