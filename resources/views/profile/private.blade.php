@extends('layouts.app',['title' => $user->username . " on " . config('app.name')])

@section('content')
@if (session('error'))
    <div class="alert alert-danger text-center font-weight-bold mb-0">
        {{ session('error') }}
    </div>
@endif
@include('profile.partial.private-info')

<div class="container">
  <div class="profile-timeline mt-2 mt-md-4">
    <div class="card">
      <div class="card-body py-5">
        <p class="text-center lead font-weight-bold mb-0">
          {{__('profile.privateProfileWarning')}}
        </p>

        @if(!Auth::check())
        <p class="text-center mb-0">{{ __('profile.alreadyFollow', ['username'=>$user->username])}}</p>
        <p class="text-center mb-0"><a href="{{route('login')}}">{{__('Log in')}}</a></p>
        <p class="text-center mb-0">{{__('profile.loginToSeeProfile')}}</p>
        @endif
      </div>
    </div>
  </div>
</div>

@endsection

@push('meta')
<meta property="og:description" content="{{$user->bio}}">
<meta property="og:image" content="{{$user->avatarUrl()}}">
@if($user->remote_url)
<meta name="robots" content="noindex, nofollow">
@endif
@endpush