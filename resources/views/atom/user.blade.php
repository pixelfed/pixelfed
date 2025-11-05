<?= '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL ?>
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/">
    <id>{{$permalink}}</id>
    <title>{{$profile['username']}} on {{config('pixelfed.domain.app', 'Pixelfed')}}</title>
    <subtitle type="html">{{strip_tags($profile['note'])}}</subtitle>
    @if($items && count($items))
        <updated>{{$items[0]['created_at']}}</updated>
    @endif

    <author>
        <name>{{$profile['username']}}</name>
        <uri>{{$profile['url']}}</uri>
    </author>

    <icon>{{$profile['avatar']}}</icon>
    <logo>{{$profile['avatar']}}</logo>

    <link rel="alternate" type="text/html" href="{{$profile['url']}}" />
    <link rel="self" type="application/atom+xml" href="{{$permalink}}" />

    @if($items && count($items))
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
                    <div class="media-gallery">
                        @foreach($item['media_attachments'] as $media)
                            <img class="media-item" src="{{ $media['url'] }}" alt="{{ $media['description'] ?? '' }}">
                        @endforeach
                    </div>
                    <p style="padding:10px;">{!! $item['content'] !!}</p>
                    ]]>
                </content>
                <link rel="alternate" href="{{ $item['url'] }}" />
                @if($item['content'] && strlen($item['content']))
                    <summary type="html">{{ $item['content'] }}</summary>
                @endif
                @foreach($item['media_attachments'] as $media)
                    <media:content
                        url="{{ $media['url'] }}"
                        type="{{ $media['mime'] }}"
                        medium="image"
                    />
                @endforeach
            </entry>
        @endforeach
    @endif
</feed>
