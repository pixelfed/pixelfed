	<div class="col-12 col-md-3 py-3" style="border-right:1px solid #ccc;">
		<ul class="nav flex-column settings-nav">
			<li class="nav-item pl-3 {{request()->is('settings/home')?'active':''}}">
				<a class="nav-link font-weight-light  text-muted" href="{{route('settings')}}">{{__('settings.sidebarAccount')}}</a>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/accessibility')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.accessibility')}}">{{__('settings.sidebarAccessibility')}}</a>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/email')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.email')}}">{{__('settings.sidebarEmail')}}</a>
			</li>
			@if(config('pixelfed.user_invites.enabled'))
			<li class="nav-item pl-3 {{request()->is('settings/invites*')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.invites')}}">{{__('settings.sidebarInvites')}}</a>
			</li>
			@endif
			<li class="nav-item pl-3 {{request()->is('settings/media*')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.media')}}">Media</a>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/notifications')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.notifications')}}">{{__('settings.sidebarNotifications')}}</a>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/password')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.password')}}">{{__('settings.sidebarPassword')}}</a>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/privacy*')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.privacy')}}">{{__('settings.sidebarPrivacy')}}</a>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/relationships*')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.relationships')}}">{{__('settings.sidebarRelationships')}}</a>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/security*')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.security')}}">{{__('settings.sidebarSecurity')}}</a>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/timeline*')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.timeline')}}">{{__('settings.sidebarTimelines')}}</a>
			</li>
			<li class="nav-item">
				<hr>
			</li>
			@if(config_cache('pixelfed.import.instagram.enabled'))
			<li class="nav-item pl-3 {{request()->is('*import*')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.import')}}">{{__('settings.sidebarImport')}}</a>
			</li>
			@endif
			<li class="nav-item pl-3 {{request()->is('settings/data-export')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.dataexport')}}">{{__('settings.sidebarDataExport')}}</a>
			</li>

			@if(config_cache('pixelfed.oauth_enabled') == true)
			<li class="nav-item">
			<hr>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/applications')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.applications')}}">{{__('settings.sidebarApplications')}}</a>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/developers')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.developers')}}">{{__('settings.sidebarDevelopers')}}</a>
			</li>
			@endif

			<li class="nav-item">
			<hr>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/labs*')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.labs')}}">{{__('settings.sidebarLabs')}}</a>
			</li>
		</ul>
	</div>
