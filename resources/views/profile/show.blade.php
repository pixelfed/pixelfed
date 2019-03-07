@extends('layouts.app',['title' => $profile->username . " on " . config('app.name')])

@section('content')
@if (session('error'))
    <div class="alert alert-danger text-center font-weight-bold mb-0">
        {{ session('error') }}
    </div>
@endif

<profile profile-id="{{$profile->id}}" :profile-settings="{{json_encode($settings)}}"></profile>

@endsection

@push('meta')<meta property="og:description" content="{{$profile->bio}}">
    <meta property="og:image" content="{{$profile->avatarUrl()}}">
    <link href="{{$profile->permalink('.atom')}}" rel="alternate" title="{{$profile->username}} on PixelFed" type="application/atom+xml">
  @if(false == $settings['crawlable'] || $profile->remote_url)
  <meta name="robots" content="noindex, nofollow">
  @endif
@endpush

@push('scripts')
<script type="text/javascript" src="{{ mix('js/profile.js') }}"></script>
<script type="text/javascript">
  new Vue({
    el: '#content'
  });
</script>
@endpush
