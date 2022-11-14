<nav class="sidenav navbar navbar-vertical fixed-left navbar-expand-xs navbar-light bg-white" id="sidenav-main">
	<div class="scrollbar-inner">
		<div class="sidenav-header  align-items-center">
			<a class="navbar-brand" href="/i/web">
				<img src="/img/pixelfed-icon-color.svg" class="navbar-brand-img">
			</a>
		</div>
		<div class="navbar-inner">
			<div class="collapse navbar-collapse" id="sidenav-collapse-main">
				<ul class="navbar-nav">
					<li class="nav-item">
						<a class="nav-link {{request()->is('i/admin/dashboard')?'active':''}} " href="/i/admin/dashboard">
							<i class="ni ni-bold-right text-primary"></i>
							<span class="nav-link-text">Dashboard</span>
						</a>
					</li>

					<li class="nav-item">
						<a class="nav-link {{request()->is('*messages*')?'active':''}}" href="{{route('admin.messages')}}">
							<i class="ni ni-bold-right text-primary"></i>
							<span class="nav-link-text">Messages</span>
						</a>
					</li>

					<li class="nav-item">
						<a class="nav-link {{request()->is('*reports*')?'active':''}}" href="{{route('admin.reports')}}">
							<i class="ni ni-bold-right text-primary"></i>
							<span class="nav-link-text">Moderation</span>
						</a>
					</li>

					<li class="nav-item">
						<a class="nav-link {{request()->is('*statuses*')?'active':''}}" href="{{route('admin.statuses')}}">
							<i class="ni ni-bold-right text-primary"></i>
							<span class="nav-link-text">Statuses</span>
						</a>
					</li>

					<li class="nav-item">
						<a class="nav-link {{request()->is('*stories*')?'active':''}}" href="{{route('admin.stories')}}">
							<i class="ni ni-bold-right text-primary"></i>
							<span class="nav-link-text">Stories <span class="badge badge-primary ml-1">NEW</span></span>
						</a>
					</li>

					<li class="nav-item">
						<a class="nav-link {{request()->is('*users*')?'active':''}}" href="{{route('admin.users')}}">
							<i class="ni ni-bold-right text-primary"></i>
							<span class="nav-link-text">Users</span>
						</a>
					</li>

					<li class="nav-item">
						<a class="nav-link {{request()->is('*admin/settings')?'active':''}}" href="{{route('admin.settings')}}">
							<i class="ni ni-bold-right text-primary"></i>
							<span class="nav-link-text">Settings</span>
						</a>
					</li>
				</ul>

				<hr class="my-3">

				<ul class="navbar-nav mb-md-3">

                    <li class="nav-item">
                        <a class="nav-link {{request()->is('*directory*')?'active':''}}" href="{{route('admin.directory')}}">
                            <i class="ni ni-bold-right text-primary"></i>
                            <span class="nav-link-text">Directory <span class="badge badge-primary ml-1">NEW</span></span>
                        </a>
                    </li>

					<li class="nav-item">
						<a class="nav-link {{request()->is('*apps*')?'active':''}}" href="{{route('admin.apps')}}">
							<i class="ni ni-bold-right text-primary"></i>
							<span class="nav-link-text">Apps</span>
						</a>
					</li>

					<li class="nav-item">
						<a class="nav-link {{request()->is('*custom-emoji*')?'active':''}}" href="{{route('admin.custom-emoji')}}">
							<i class="ni ni-bold-right text-primary"></i>
							<span class="nav-link-text">Custom Emoji <span class="badge badge-primary ml-1">NEW</span></span>
						</a>
					</li>

					<li class="nav-item">
						<a class="nav-link {{request()->is('*diagnostics*')?'active':''}}" href="{{route('admin.diagnostics')}}">
							<i class="ni ni-bold-right text-primary"></i>
							<span class="nav-link-text">Diagnostics <span class="badge badge-primary ml-1">NEW</span></span>
						</a>
					</li>

					<li class="nav-item">
						<a class="nav-link {{request()->is('*hashtags*')?'active':''}}" href="{{route('admin.hashtags')}}">
							<i class="ni ni-bold-right text-primary"></i>
							<span class="nav-link-text">Hashtags</span>
						</a>
					</li>

					<li class="nav-item">
						<a class="nav-link" href="/horizon">
							<i class="ni ni-bold-right text-primary"></i>
							<span class="nav-link-text">Horizon</span>
						</a>
					</li>

					<li class="nav-item">
						<a class="nav-link {{request()->is('*instances*')?'active':''}}" href="{{route('admin.instances')}}">
							<i class="ni ni-bold-right text-primary"></i>
							<span class="nav-link-text">Instances</span>
						</a>
					</li>

					<li class="nav-item">
						<a class="nav-link {{request()->is('*media*')?'active':''}}" href="{{route('admin.media')}}">
							<i class="ni ni-bold-right text-primary"></i>
							<span class="nav-link-text">Media</span>
						</a>
					</li>

					<li class="nav-item">
						<a class="nav-link {{request()->is('*site-news*')?'active':''}}" href="/i/admin/site-news">
							<i class="ni ni-bold-right text-primary"></i>
							<span class="nav-link-text">Newsroom</span>
						</a>
					</li>

					<li class="nav-item">
						<a class="nav-link {{request()->is('*profiles*')?'active':''}}" href="/i/admin/profiles">
							<i class="ni ni-bold-right text-primary"></i>
							<span class="nav-link-text">Profiles</span>
						</a>
					</li>

					<li class="nav-item">
						<a class="nav-link {{request()->is('*settings/pages*')?'active':''}}" href="/i/admin/settings/pages">
							<i class="ni ni-bold-right text-primary"></i>
							<span class="nav-link-text">Pages</span>
						</a>
					</li>

                    <li class="nav-item">
                        <a class="nav-link {{request()->is('*stats')?'active':''}}" href="/i/admin/stats">
                            <i class="ni ni-bold-right text-primary"></i>
                            <span class="nav-link-text">Stats</span>
                        </a>
                    </li>

					<li class="nav-item">
						<a class="nav-link {{request()->is('*settings/system')?'active':''}}" href="/i/admin/settings/system">
							<i class="ni ni-bold-right text-primary"></i>
							<span class="nav-link-text">System</span>
						</a>
					</li>

				</ul>
			</div>
		</div>
	</div>
</nav>
