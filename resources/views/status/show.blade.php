@extends('layouts.app',['title' => "{$user->username} shared a post"])

@section('content')
<noscript>
  <div class="container">
    <p class="pt-5 text-center lead">Please enable javascript to view this content.</p>
  </div>
</noscript>
<div class="mt-md-4"></div>
<post-component status-template="{{$status->viewType()}}" status-id="{{$status->id}}" status-username="{{$status->profile->username}}" status-url="{{$status->url()}}" status-profile-url="{{$status->profile->url()}}" status-avatar="{{$status->profile->avatarUrl()}}" status-profile-id="{{$status->profile_id}}" profile-layout="metro"></post-component>


@endsection

@push('meta')

    <meta property="og:description" content="{{ $status->caption }}">
    <meta property="og:image" content="{{$status->thumb()}}">
    <link href='{{$status->url()}}' rel='alternate' type='application/activity+json'>
    <meta name="twitter:card" content="summary_large_image">
    @if($status->viewType() == "video" || $status->viewType() == "video:album")
        <meta property="og:video" content="{{$status->mediaUrl()}}">
    @endif
@endpush

@push('scripts')
<script type="text/javascript" src="{{ mix('js/status.js') }}"></script>
<script type="text/javascript">App.boot();</script>
@endpush
