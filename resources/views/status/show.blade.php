@extends('layouts.app', [
    'title' => $desc ?? "{$user->username} shared a post",
    'ogTitle' => $ogTitle
])

@php
$s = \App\Services\StatusService::get($status->id, false);
$displayName = $s && $s['account'] ? $s['account']['display_name'] : false;
$captionPreview = false;
$domain = $displayName ? '@' . parse_url($s['account']['url'], PHP_URL_HOST) : '';
$wf = $displayName ? $s['account']['username'] . $domain : '';
$ogTitle = $displayName ? $displayName . ' (@' . $s['account']['username'] . $domain . ')' : '';
$mediaCount = $s['media_attachments'] && count($s['media_attachments']) ? count($s['media_attachments']) : 0;
$mediaSuffix = $mediaCount < 2 ? '' : 's';
$ogDescription = $s['content_text'] ? $s['content_text'] : 'Attached: ' . $mediaCount . ' ' . $s['media_attachments'][0]['type'] . $mediaSuffix;
if($s['content_text']) {
    $captionLen = strlen($s['content_text']);
    $captionPreview = $captionLen > 40 ? substr($s['content_text'], 0, 40) . '…' : $s['content_text'];
}
$desc = false;
if($displayName && $captionPreview) {
    $desc = $displayName . ': "' . $captionPreview . '" - Pixelfed';
} else if($displayName) {
    $desc = $displayName . ': Shared a new post - Pixelfed';
}

@endphp

@section('content')
<noscript>
  <div class="container">
    <p class="pt-5 text-center lead">Please enable javascript to view this content.</p>
  </div>
</noscript>
<div class="mt-md-4"></div>
<post-component
    status-template="{{$status->viewType()}}"
    status-id="{{$status->id}}"
    status-username="{{$s['account']['username']}}"
    status-url="{{$s['url']}}"
    status-profile-url="{{$s['account']['url']}}"
    status-avatar="{{$s['account']['avatar']}}"
    status-profile-id="{{$status->profile_id}}"
    profile-layout="metro" />


@endsection

@push('meta')@if($mediaCount && $s['pf_type'] === "photo" || $s['pf_type'] === "photo:album")
<meta property="og:image" content="{{$s['media_attachments'][0]['url']}}">
    @elseif($mediaCount && $s['pf_type'] === "video" || $s['pf_type'] === "video:album")<meta property="og:video" content="{{$s['media_attachments'][0]['url']}}">
    @endif<meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:published_time" content="{{$s['created_at']}}">
    <meta property="profile:username" content="{{ $wf }}">
    <link href='{{$s['url']}}' rel='alternate' type='application/activity+json'>
    <meta name="twitter:card" content="summary">
    <meta name="description" content="{{ $ogDescription }}">
@endpush

@push('scripts')
<script type="text/javascript" src="{{ mix('js/status.js') }}"></script>
<script type="text/javascript">App.boot();</script>
@endpush
