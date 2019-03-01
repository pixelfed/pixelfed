@extends('layouts.app',['title' => "A post by " . $user->username])

@section('content')
<noscript>
  <div class="card">
    <div class="card-body container text-center font-weight-bold">
      This website requires javascript, please enable it and refresh the page.
    </div>
  </div>
</noscript>
<div class="mt-md-4"></div>
<post-component status-template="{{$status->viewType()}}" status-id="{{$status->id}}" status-username="{{$status->profile->username}}" status-url="{{$status->url()}}" status-profile-url="{{$status->profile->url()}}" status-avatar="{{$status->profile->avatarUrl()}}"></post-component>


@endsection

@push('meta')
  <meta property="og:description" content="{{ $status->caption }}">
  <meta property="og:image" content="{{$status->mediaUrl()}}">
  <link href='{{$status->url()}}' rel='alternate' type='application/activity+json'>
  <meta name="twitter:card" content="summary_large_image">
@endpush

@push('scripts')
<script type="text/javascript" src="{{ mix('js/status.js') }}"></script>
<script type="text/javascript">
$(document).ready(function() {
  new Vue({ 
    el: '#content'
  });
});
</script>
@endpush
