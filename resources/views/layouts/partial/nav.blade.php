<nav class="navbar navbar-expand navbar-light navbar-laravel shadow-none border-bottom sticky-top py-1">
	<div class="container">
			<a class="navbar-brand d-flex align-items-center" href="{{ route('timeline.personal') }}" title="Logo">
				<img src="/img/pixelfed-icon-color.svg" height="30px" class="px-2" loading="eager" alt="Pixelfed logo">
				<span class="font-weight-bold mb-0 d-none d-sm-block" style="font-size:20px;">{{ config_cache('app.name') }}</span>
			</a>

			<div class="collapse navbar-collapse">
			@auth
				<div class="navbar-nav d-none d-md-block mx-auto">
				  <form class="form-inline search-bar" method="get" action="/i/results">
					<input class="form-control form-control-sm" name="q" placeholder="{{__('navmenu.search')}}" aria-label="search" autocomplete="off" required style="line-height: 0.6;width:200px" role="search">
				  </form>
				</div>
			@endauth

			@guest

				<ul class="navbar-nav ml-auto">
					<li>
						<a class="nav-link font-weight-bold text-dark" href="{{ route('login') }}" title="Login">
							{{__('navmenu.login')}}
						</a>
					</li>
				@if(config_cache('pixelfed.open_registration') && in_array(config_cache('system.user_mode'), ['default', 'admin']))
					<li>
						<a class="ml-3 nav-link font-weight-bold text-dark" href="{{ route('register') }}" title="Register">
							{{__('navmenu.register')}}
						</a>
					</li>
				@endif
			@else
				<div class="ml-auto">
					<ul class="navbar-nav align-items-center">
						<li class="nav-item px-md-2 d-none d-md-block">
							<a class="nav-link font-weight-bold text-dark" href="/" title="Home" data-toggle="tooltip" data-placement="bottom">
								<i class="fas fa-home fa-lg"></i>
								<span class="sr-only">{{__('navmenu.home')}}</span>
							</a>
						</li>
						<li class="nav-item px-md-2">
							<a class="nav-link font-weight-bold text-dark" href="/account/direct" title="Direct" data-toggle="tooltip" data-placement="bottom">
								<i class="far fa-comment-dots fa-lg"></i>
								<span class="sr-only">{{__('navmenu.direct')}}</span>
							</a>
						</li>
						<li class="nav-item px-md-2 d-none d-md-block">
							<a class="nav-link font-weight-bold text-dark" href="/account/activity" title="Notifications" data-toggle="tooltip" data-placement="bottom">
								<i class="far fa-bell fa-lg"></i>
								<span class="sr-only">{{__('navmenu.notifications')}}</span>
							</a>
						</li>
						<li class="nav-item px-md-2 d-none d-md-block">
							<div class="nav-link btn btn-primary btn-sm py-1 font-weight-bold text-white" title="Compose" data-toggle="tooltip" data-placement="bottom" onclick="App.util.compose.post()">
								<span>{{__('navmenu.newPost')}}</span>
							</div>
						</li>
						<li class="nav-item dropdown ml-2">
							<a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="User Menu" data-toggle="tooltip" data-placement="bottom">
								<i class="far fa-user fa-lg text-dark"></i>
								<span class="sr-only">User Menu</span>
								<img class="d-none" src="/storage/avatars/default.png?v=0" class="rounded-circle border shadow" width="34" height="34" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';">
							</a>

							<div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
								@if(config('federation.network_timeline'))
								<a class="dropdown-item font-weight-bold" href="{{route('timeline.public')}}">
									<span class="fas fa-stream pr-2 text-lighter"></span>
									{{__('navmenu.public')}}
								</a>
								<a class="dropdown-item font-weight-bold" href="{{route('timeline.network')}}">
									<span class="fas fa-globe pr-2 text-lighter"></span>
									{{__('navmenu.network')}}
								</a>
								@else
								<a class="dropdown-item font-weight-bold" href="/">
									<span class="fas fa-home pr-2 text-lighter"></span>
									{{__('navmenu.home')}}
								</a>
								<a class="dropdown-item font-weight-bold" href="{{route('timeline.public')}}">
									<span class="fas fa-stream pr-2 text-lighter"></span>
									{{__('navmenu.public')}}
								</a>
								@endif
								<div class="dropdown-divider"></div>
								<a class="dropdown-item font-weight-bold" href="{{route('discover')}}">
									<span class="far fa-compass pr-2 text-lighter"></span>
									{{__('navmenu.discover')}}
								</a>
								<a class="dropdown-item font-weight-bold" href="/i/stories/new">
									<span class="fas fa-history text-lighter pr-2"></span>
									{{__('navmenu.stories')}}
								</a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item font-weight-bold" href="/i/me">
									<span class="far fa-user pr-2 text-lighter"></span>
									{{__('navmenu.myProfile')}}
								</a>
								<a class="dropdown-item font-weight-bold" href="{{route('settings')}}">
									<span class="fas fa-cog pr-2 text-lighter"></span>
									{{__('navmenu.settings')}}
								</a>
								@if(Auth::user()->is_admin == true)
								<a class="dropdown-item font-weight-bold" href="{{ route('admin.home') }}">
									<span class="fas fa-shield-alt fa-sm pr-2 text-lighter"></span>
									{{__('navmenu.admin')}}
								</a>
								@endif
								<div class="dropdown-divider"></div>
								<a class="dropdown-item font-weight-bold" href="{{ route('logout') }}"
								   onclick="event.preventDefault();
												 document.getElementById('logout-form').submit();">
									<span class="fas fa-sign-out-alt pr-2"></span>
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
