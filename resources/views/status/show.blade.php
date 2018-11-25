@extends('layouts.app',['title' => $user->username . " posted a photo: " . $status->likes_count . " likes, " . $status->comments_count . " comments" ])

@section('content')


<post-component status-template="{{$status->viewType()}}" status-id="{{$status->id}}" status-username="{{$status->profile->username}}" status-url="{{$status->url()}}" status-profile-url="{{$status->profile->url()}}" status-avatar="{{$status->profile->avatarUrl()}}"></post-component>


@endsection

@push('meta')
  <meta property="og:description" content="{{ $status->caption }}">
  <meta property="og:image" content="{{$status->mediaUrl()}}">
  <link href='{{$status->url()}}' rel='alternate' type='application/activity+json'>
@endpush

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {
  new Vue({ 
    el: '#content'
  });
});
</script>
@endpush
