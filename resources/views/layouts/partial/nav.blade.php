<nav class="navbar navbar-expand navbar-light navbar-laravel shadow-none border-bottom sticky-top py-1">
	<div class="container">
			<a class="navbar-brand d-flex align-items-center" href="{{ config('app.url') }}" title="Logo">
				<img src="/img/pixelfed-icon-color.svg" height="30px" class="px-2" loading="eager" alt="Pixelfed logo">
				<span class="font-weight-bold mb-0 d-none d-sm-block" style="font-size:20px;">{{ config_cache('app.name') }}</span>
			</a>

			<div class="collapse navbar-collapse">
			@auth
				<div class="navbar-nav d-none d-md-block mx-auto">
				  <form class="form-inline search-bar" method="get" action="/i/results" role="search">
					<input class="form-control form-control-sm rounded-pill bg-light" name="q" placeholder="{{__('navmenu.search')}}" aria-label="search" autocomplete="off" required style="line-height: 0.6;width:200px">
				  </form>
				</div>
			@endauth

			@guest

				<ul class="navbar-nav ml-auto">
					<li>
						<a class="nav-link font-weight-bold text-dark" href="/login" title="Login">
							{{ __('Login') }}
						</a>
					</li>
				@if(config_cache('pixelfed.open_registration') && in_array(config_cache('system.user_mode'), ['default', 'admin']))
					<li>
						<a class="ml-3 nav-link font-weight-bold text-dark" href="/register" title="Register">
							{{ __('Register') }}
						</a>
					</li>
				@endif
			@else
				<div class="ml-auto">
					<ul class="navbar-nav align-items-center">
						<li class="nav-item px-md-2 d-none d-md-block">
							<a class="nav-link font-weight-bold text-dark" href="/" title="Home" data-toggle="tooltip" data-placement="bottom">
								<i class="fal fa-home fa-lg" style="font-size: 22px;"></i>
								<span class="sr-only">Home</span>
							</a>
						</li>
						<li class="nav-item px-md-2">
							<a class="nav-link font-weight-bold text-dark" href="/account/direct" title="Direct" data-toggle="tooltip" data-placement="bottom">
								<i class="fal fa-location-circle fa-lg" style="font-size: 22px;"></i>
								<span class="sr-only">Direct</span>
							</a>
						</li>
						<li class="nav-item px-md-2 d-none d-md-block">
							<a class="nav-link font-weight-bold text-dark" href="/account/activity" title="Notifications" data-toggle="tooltip" data-placement="bottom">
								<i class="fal fa-bell fa-lg" style="font-size: 22px;"></i>
								<span class="sr-only">Notifications</span>
							</a>
						</li>
						<li class="nav-item px-md-2 d-none d-md-block">
							<div class="nav-link btn btn-primary lead btn-sm px-3 py-1 text-white shadow rounded-pill d-flex align-items-center" title="Compose" data-toggle="tooltip" data-placement="bottom" onclick="App.util.compose.post()">
								<i class="fal fa-plus-circle" style="font-size:14px;margin-right:6px;"></i>
								New
							</div>
						</li>
						<li class="nav-item dropdown ml-2">
							<a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="User Menu" data-toggle="tooltip" data-placement="bottom">
								<i class="far fa-user fa-lg text-dark"></i>
								<span class="sr-only">User Menu</span>
								<img class="d-none" src="/storage/avatars/default.png?v=0" class="rounded-circle border shadow" width="38" height="38" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';">
							</a>

							<div class="dropdown-menu dropdown-menu-right px-0 shadow" aria-labelledby="navbarDropdown" style="min-width: 220px;">
								@if(config('federation.network_timeline'))
								<a class="dropdown-item lead" href="/">
									<span style="width: 50px;margin-right:14px;">
										<span class="fal fa-home text-lighter fa-lg"></span>
									</span>
									My Feed
								</a>
								<a class="dropdown-item lead" href="/i/web/timeline/local">
									<span style="width: 50px;margin-right:14px;">
										<span class="fal fa-stream text-lighter fa-lg"></span>
									</span>
									Public Feed
								</a>
								<a class="dropdown-item lead" href="/i/web/timeline/global">
									<span style="width: 50px;margin-right:14px;">
										<span class="fal fa-globe text-lighter fa-lg"></span>
									</span>
									Network Feed
								</a>
								@else
								<a class="dropdown-item lead" href="/">
									<span style="width: 50px;margin-right:14px;">
										<span class="fal fa-home text-lighter fa-lg"></span>
									</span>
									Home
								</a>
								<a class="dropdown-item lead" href="/i/web/timeline/local">
									<span style="width: 50px;margin-right:14px;">
										<span class="fas fa-stream text-lighter fa-lg"></span>
									</span>
									Public
								</a>
								@endif
								<div class="dropdown-divider"></div>
								<a class="dropdown-item lead" href="/i/web/discover">
									<span style="width: 50px;margin-right:14px;">
										<span class="fal fa-compass text-lighter fa-lg"></span>
									</span>
									{{__('navmenu.discover')}}
								</a>

								@if(config_cache('instance.stories.enabled'))
								<a class="dropdown-item lead" href="/i/stories/new">
									<span style="width: 50px;margin-right:14px;">
										<span class="fal fa-history text-lighter fa-lg"></span>
									</span>
									Stories
								</a>
								@endif
								<div class="dropdown-divider"></div>
								<a class="dropdown-item lead" href="/i/me">
									<span style="width: 50px;margin-right:14px;">
										<span class="fal fa-user text-lighter fa-lg"></span>
									</span>
									{{__('navmenu.myProfile')}}
								</a>
								<a class="dropdown-item lead" href="/settings/home">
									<span style="width: 50px;margin-right:14px;">
										<span class="fal fa-cog text-lighter fa-lg"></span>
									</span>
									{{__('navmenu.settings')}}
								</a>
								@if(Auth::user()->is_admin == true)
								<a class="dropdown-item lead" href="/i/admin/dashboard">
									<span style="width: 50px;margin-right:14px;">
										<span class="fal fa-shield-alt text-lighter fa-lg"></span>
									</span>
									{{__('navmenu.admin')}}
								</a>
								@endif
								<div class="dropdown-divider"></div>
								<a class="dropdown-item lead" href="/logout"
								   onclick="event.preventDefault();
												 document.getElementById('logout-form').submit();">
									<span style="width: 50px;margin-right:14px;" class="text-lighter">
										<span class="fal fa-sign-out-alt fa-lg"></span>
									</span>
									<span class="text-lighter">{{ __('navmenu.logout') }}</span>
								</a>

								<form id="logout-form" action="/logout" method="POST" style="display: none;">
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
