<nav class="navbar navbar-top navbar-expand navbar-dark bg-primary border-bottom">
		<div class="container-fluid">
			<div class="collapse navbar-collapse" id="navbarSupportedContent">
				<form class="navbar-search navbar-search-light form-inline mr-sm-3" id="navbar-search-main" method="get" action="/i/web">
					<input type="hidden" name="src" value="ac">
					<div class="form-group mb-0">
						<div class="input-group input-group-alternative input-group-merge">
							<div class="input-group-prepend">
								<span class="input-group-text"><i class="fas fa-search"></i></span>
							</div>
							<input type="text" class="form-control" name="q" placeholder="{{__('navmenu.search')}}">
						</div>
					</div>
					<button type="button" class="close" data-action="search-close" data-target="#navbar-search-main" aria-label="Close">
						<span aria-hidden="true">×</span>
					</button>
				</form>
				<ul class="navbar-nav align-items-center  ml-md-auto ">
					<li class="nav-item d-xl-none">
						<div class="pr-3 sidenav-toggler sidenav-toggler-dark" data-action="sidenav-pin" data-target="#sidenav-main">
							<div class="sidenav-toggler-inner">
								<i class="sidenav-toggler-line"></i>
								<i class="sidenav-toggler-line"></i>
								<i class="sidenav-toggler-line"></i>
							</div>
						</div>
					</li>
					<li class="nav-item d-sm-none">
						<a class="nav-link" href="#" data-action="search-show" data-target="#navbar-search-main">
							<i class="ni ni-zoom-split-in"></i>
						</a>
					</li>
				</ul>
				<ul class="navbar-nav align-items-center  ml-auto ml-md-0 ">
					<li class="nav-item dropdown">
						<a class="nav-link pr-0" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<div class="media align-items-center">
								<span class="avatar avatar-sm rounded-circle">
									<img alt="avatar" src="{{request()->user()->avatarUrl()}}" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';" width="36" height="36" style="object-fit: cover;">
								</span>
								<div class="media-body  ml-2  d-none d-lg-block">
									<span class="mb-0 text-sm font-weight-bold">{{request()->user()->username}}</span>
								</div>
							</div>
						</a>
						<div class="dropdown-menu  dropdown-menu-right ">
							<a href="/i/web" class="dropdown-item d-flex align-items-center">
								<span style="width:30px;">
									<i class="far fa-home text-light"></i>
								</span>
								<span class="font-weight-bold">Home</span>
							</a>

							<a href="/i/web/discover" class="dropdown-item d-flex align-items-center">
								<span style="width:30px;">
									<i class="far fa-compass text-light"></i>
								</span>
								<span class="font-weight-bold">Discover</span>
							</a>

							<div class="dropdown-divider"></div>

							<a href="/i/me" class="dropdown-item d-flex align-items-center">
								<span style="width:30px;">
									<i class="far fa-user text-light"></i>
								</span>
								<span class="font-weight-bold">Profile</span>
							</a>
							<a href="/settings/home" class="dropdown-item d-flex align-items-center">
								<span style="width:30px;">
									<i class="far fa-cog text-light"></i>
								</span>
								<span class="font-weight-bold">Settings</span>
							</a>

							<div class="dropdown-divider"></div>

							<a
								href="#"
								class="dropdown-item d-flex align-items-center"
								onclick="event.preventDefault();document.getElementById('logout-form').submit();">
								<span style="width:30px;">
									<i class="far fa-sign-out text-light"></i>
								</span>
								<span class="font-weight-bold">{{ __('navmenu.logout') }}</span>
							</a>
							<form id="logout-form" action="/logout" method="POST" style="display: none;">
								@csrf
							</form>
						</div>
					</li>
				</ul>
			</div>
		</div>
	</nav>
