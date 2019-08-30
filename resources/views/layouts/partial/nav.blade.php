<nav class="navbar navbar-expand navbar-light navbar-laravel shadow-none border-bottom border sticky-top py-1">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="{{ route('timeline.personal') }}" title="Logo">
            <img src="/img/pixelfed-icon-color.svg" height="30px" class="px-2" loading="eager">
            <span class="font-weight-bold mb-0 d-none d-sm-block" style="font-size:20px;">{{ config('app.name', 'pixelfed') }}</span>
        </a>

        <div class="collapse navbar-collapse">
        @auth
            <ul class="navbar-nav d-none d-md-block mx-auto">
              <form class="form-inline search-bar" method="get" action="/i/results">
                <input class="form-control form-control-sm" name="q" placeholder="{{__('navmenu.search')}}" aria-label="search" autocomplete="off" required style="line-height: 0.6;width:200px">
              </form>
            </ul>
        @endauth

        @guest
                
            <ul class="navbar-nav ml-auto">
                <li>
                    <a class="nav-link font-weight-bold text-primary" href="{{ route('login') }}" title="Login">
                        {{ __('Login') }}
                    </a>
                </li>
            @if(config('pixelfed.open_registration'))
                    <li>
                        <a class="nav-link font-weight-bold" href="{{ route('register') }}" title="Register">
                            {{ __('Register') }}
                        </a>
                    </li>
            @endif
        @else
            <div class="ml-auto">
                <ul class="navbar-nav">
                    <div class="d-none d-md-block">
                        <li class="nav-item px-md-2">
                            <a class="nav-link font-weight-bold text-muted" href="/" title="Home Timeline" data-toggle="tooltip" data-placement="bottom">
                                <i class="fas fa-home fa-lg"></i>
                            </a>
                        </li>
                    </div>
                    <li class="d-block d-md-none"></li>
                    <div class="d-none d-md-block">
                        <li class="nav-item px-md-2">
                            <a class="nav-link font-weight-bold text-muted" href="{{route('discover')}}" title="Discover" data-toggle="tooltip" data-placement="bottom">
                                <i class="far fa-compass fa-lg"></i>
                            </a>
                        </li>
                    </div>
                    <div class="d-none d-md-block">
                        <li class="nav-item px-md-2">
                            <div title="Create new post" data-toggle="tooltip" data-placement="bottom">
                                <a href="{{route('compose')}}" class="nav-link" data-toggle="modal" data-target="#composeModal">
                                  <i class="fas fa-camera-retro fa-lg text-muted"></i>
                                </a>
                            </div>
                        </li>
                    </div>
                    <li class="nav-item dropdown ml-2">
                        <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="User Menu" data-toggle="tooltip" data-placement="bottom">
                            <i class="far fa-user fa-lg text-muted"></i>
                        </a>

                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                            <a class="dropdown-item font-weight-bold" href="/i/me">
                                <span class="far fa-user pr-1"></span>
                                {{__('navmenu.myProfile')}}
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
                </div>
        @endguest
            </ul>
        </div>
    </div>
</nav>