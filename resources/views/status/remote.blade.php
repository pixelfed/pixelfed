@extends('layouts.app')

@section('content')
<div class="mt-md-4"></div>
<remote-post status-template="{{$status->viewType()}}" status-id="{{$status->id}}" status-username="{{$status->profile->username}}" status-url="{{$status->url()}}" status-profile-url="{{$status->profile->url()}}" status-avatar="{{$status->profile->avatarUrl()}}" status-profile-id="{{$status->profile_id}}" profile-layout="metro"></remote-post>


@endsection

@push('meta')
<meta name="robots" content="noindex, noimageindex, nofollow, nosnippet, noarchive">
@endpush

@push('scripts')
<script type="text/javascript" src="{{ mix('js/rempos.js') }}"></script>
<script type="text/javascript">App.boot()</script>
@endpush
