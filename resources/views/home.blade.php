@extends('layouts.app',['title' => 'Welcome to ' . config('app.name')])

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 p-sm-3 p-0">
            <a class="card font-weight-bold text-center h-100" href="{{Auth::user()->url()}}">
                <div class="card-body">
                    <div class="row">
                        <div class="col-3">
                            <img class="img-thumbnail rounded-circle pr-1 w-25" src="{{Auth::user()->profile->avatarUrl()}}">
                        </div>
                        <h1 class="col-9 my-auto">&commat;{{Auth::user()->username}}</h1>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4 p-sm-3 p-0">
            <a class="card font-weight-bold text-center h-100" href="{{route('timeline.personal')}}">
                <div class="card-body d-flex flex-column text-dark">
                    <h2 class="fas fa-list-alt mt-auto"></h2>
                    <p class="mb-auto">{{__('navmenu.myTimeline')}}</p>
                </div>
            </a>
        </div>
        <div class="col-md-4 p-sm-3 p-0">
            <a class="card font-weight-bold text-center h-100" href="{{route('timeline.public')}}">
                <div class="card-body d-flex flex-column text-dark">
                    <h2 class="fas fa-list mt-auto"></h2>
                    <p class="mb-auto">{{__('navmenu.publicTimeline')}}</p>
                </div>
            </a>
        </div>
        <div class="col-md-4 p-sm-3 p-0">
            <a class="card font-weight-bold text-center h-100" href="{{route('remotefollow')}}">
                <div class="card-body d-flex flex-column text-dark">
                    <h2 class="fas fa-user-plus mt-auto"></h2>
                    <p class="mb-auto">{{__('navmenu.remoteFollow')}}</p>
                </div>
            </a>
        </div>
        <div class="col-md-4 p-sm-3 p-0">
            <a class="card font-weight-bold text-center h-100" href="{{route('settings')}}">
                <div class="card-body d-flex flex-column text-dark">
                    <h2 class="fas fa-cog mt-auto"></h2>
                    <p class="mb-auto">{{__('navmenu.settings')}}</p>
                </div>
            </a>
        </div>
        <div class="col-md-4 p-sm-3 p-0">
            <a class="card text-white font-weight-bold text-center h-100 bg-danger" href="{{ route('logout') }}">
                <div class="card-body d-flex flex-column">
                    <h2 class="fas fa-sign-out-alt mt-auto"></h2>
                    <p class="mb-auto">{{ __('navmenu.logout') }}</p>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection

@push('meta')
<meta property="og:description" content="">
@endpush


@push('styles')
@endpush
