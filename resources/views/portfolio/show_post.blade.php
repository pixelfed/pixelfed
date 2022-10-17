@extends('portfolio.layout', ['title' => "@{$user['username']}'s Portfolio Photo"])

@section('content')
<portfolio-post initial-data="{{json_encode(['profile' => $user, 'post' => $post, 'authed' => $authed ? true : false])}}" />
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/portfolio.js') }}"></script>
<script type="text/javascript">
    App.boot();
</script>
@endpush

@push('meta')<meta property="og:description" content="{{ $post['content_text'] }}">
    <meta property="og:image" content="{{ $post['media_attachments'][0]['url']}}">
    <meta name="twitter:card" content="summary_large_image">
@endpush
