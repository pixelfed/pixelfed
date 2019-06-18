<nav class="navbar navbar-expand navbar-light navbar-laravel sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="{{ route('timeline.personal') }}" title="Logo">
            <img src="/img/pixelfed-icon-color.svg" height="30px" class="px-2">
            <span class="font-weight-bold mb-0 d-none d-sm-block" style="font-size:20px;">{{ config('app.name', 'pixelfed') }}</span>
        </a>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            @auth
            <ul class="navbar-nav mx-auto pr-3">
              <form class="form-inline search-bar" method="get" action="/i/results">
                <div class="input-group">
                    <input class="form-control" name="q" placeholder="{{__('navmenu.search')}}" aria-label="search" autocomplete="off" required>
                    <div class="input-group-append">
                        <button class="btn btn-outline-primary" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </div>
              </form>
            </ul>
            @endauth

                @guest
            <ul class="navbar-nav ml-auto">
                    <li><a class="nav-link font-weight-bold text-primary" href="{{ route('login') }}" title="Login">{{ __('Login') }}</a></li>
                    <li><a class="nav-link font-weight-bold" href="{{ route('register') }}" title="Register">{{ __('Register') }}</a></li>
                @else
            <ul class="navbar-nav ml-auto">
                    <div class="d-none d-md-block">
                        <li class="nav-item px-md-2">
                            <a class="nav-link font-weight-bold {{request()->is('/') ?'text-dark':'text-muted'}}" href="/" title="Home Timeline" data-toggle="tooltip" data-placement="bottom">
                                <i class="fas fa-home fa-lg"></i>
                            </a>
                        </li>
                    </div>
                    {{-- <div class="d-none d-md-block">
                        <li class="nav-item px-md-2">
                            <a class="nav-link font-weight-bold {{request()->is('timeline/public') ?'text-primary':''}}" href="/timeline/public" title="Public Timeline" data-toggle="tooltip" data-placement="bottom">
                               <i class="far fa-map fa-lg"></i>
                           </a>
                       </li>
                   </div>  --}}
                    
                    <li class="d-block d-md-none">

                    </li>

                    {{-- <li class="pr-2">
                        <a class="nav-link font-weight-bold {{request()->is('timeline/network') ?'text-primary':''}}" href="{{route('timeline.network')}}" title="Network Timeline">
                            <i class="fas fa-globe fa-lg"></i>
                        </a>
                    </li> --}}
                    <div class="d-none d-md-block">
                        <li class="nav-item px-md-2">
                            <a class="nav-link font-weight-bold {{request()->is('*discover*') ?'text-dark':'text-muted'}}" href="{{route('discover')}}" title="Discover" data-toggle="tooltip" data-placement="bottom">
                                <i class="far fa-compass fa-lg"></i>
                            </a>
                        </li>
                    </div>
                    <div class="d-none d-md-block">
                        <li class="nav-item px-md-2">
                            <div title="Create new post" data-toggle="tooltip" data-placement="bottom">
                                <a href="{{route('compose')}}" class="nav-link" data-toggle="modal" data-target="#composeModal">
                                  <i class="fas fa-camera-retro fa-lg text-primary"></i>
                                </a>
                            </div>
                        </li>
                    </div>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="User Menu" data-toggle="tooltip" data-placement="bottom">
                            <img class="rounded-circle box-shadow mr-1" src="{{Auth::user()->profile->avatarUrl()}}" width="26px" height="26px">
                        </a>

                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                            <a class="dropdown-item font-weight-ultralight text-truncate" href="{{Auth::user()->url()}}">
                                <img class="rounded-circle box-shadow mr-1" src="{{Auth::user()->profile->avatarUrl()}}" width="26px" height="26px">
                                &commat;{{Auth::user()->username}}
                                <p class="small mb-0 text-muted text-center">{{__('navmenu.viewMyProfile')}}</p>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="d-block d-md-none dropdown-item font-weight-bold" href="{{route('discover')}}">
                                <span class="far fa-compass pr-1"></span>
                                {{__('navmenu.discover')}}
                            </a>
                            <a class="dropdown-item font-weight-bold" href="{{route('notifications')}}">
                                <span class="far fa-bell pr-1"></span>
                                Notifications
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item font-weight-bold" href="{{route('timeline.personal')}}">
                                <span class="fas fa-home pr-1"></span>
                                {{__('navmenu.myTimeline')}}
                            </a>
                            <a class="dropdown-item font-weight-bold" href="{{route('timeline.public')}}">
                                <span class="far fa-map pr-1"></span>
                                {{__('navmenu.publicTimeline')}}
                            </a>
                           {{-- <a class="dropdown-item font-weight-bold" href="{{route('timeline.network')}}">
                                <span class="fas fa-globe pr-1"></span>
                                Network Timeline
                            </a> --}}
                            {{-- <a class="dropdown-item font-weight-bold" href="{{route('messages')}}">
                                <span class="far fa-envelope pr-1"></span>
                                {{__('navmenu.directMessages')}}
                            </a> 
                            <a class="dropdown-item font-weight-bold" href="{{route('account.circles')}}">
                                <span class="far fa-circle pr-1"></span>
                                {{__('Circles')}}
                            </a>--}}
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item font-weight-bold" href="{{route('settings')}}">
                                <span class="fas fa-cog pr-1"></span>
                                {{__('navmenu.settings')}}
                            </a>
                            @if(Auth::user()->is_admin == true)
                            <a class="dropdown-item font-weight-bold" href="{{ route('admin.home') }}">
                                <span class="fas fa-cogs pr-1"></span>
                                {{__('navmenu.admin')}}
                            </a>
                            <div class="dropdown-divider"></div>
                            @endif
                            <a class="dropdown-item font-weight-bold" href="{{ route('logout') }}"
                               onclick="event.preventDefault();
                                             document.getElementById('logout-form').submit();">
                                <span class="fas fa-sign-out-alt pr-1"></span>
                                {{ __('navmenu.logout') }}
                            </a>

                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </div>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>