	<div class="col-12 col-md-3">
		<ul class="nav flex-column settings-nav py-3">
			<li class="nav-item pl-3 {{request()->is('settings/home')?'active':''}}">
				<a class="nav-link font-weight-light  text-muted" href="{{route('settings')}}">Account</a>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/accessibility')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.accessibility')}}">Accessibility</a>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/email')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.email')}}">Email</a>
			</li>
			@if(config('pixelfed.user_invites.enabled'))
			<li class="nav-item pl-3 {{request()->is('settings/invites*')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.invites')}}">Invites</a>
			</li>
			@endif
			<li class="nav-item pl-3 {{request()->is('settings/media*')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.media')}}">Media</a>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/notifications')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.notifications')}}">Notifications</a>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/password')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.password')}}">Password</a>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/privacy*')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.privacy')}}">Privacy</a>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/relationships*')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.relationships')}}">Relationships</a>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/security*')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.security')}}">Security</a>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/timeline*')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.timeline')}}">Timelines</a>
			</li>
			<li class="nav-item">
				<hr>
			</li>
			<li class="nav-item pl-3 {{request()->is('*import*')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.import')}}">Import</a>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/data-export')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.dataexport')}}">Export</a>
			</li>

			@if(config_cache('pixelfed.oauth_enabled') == true)
			<li class="nav-item">
			<hr>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/applications')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.applications')}}">Applications</a>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/developers')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.developers')}}">Developers</a>
			</li>
			@endif

			<li class="nav-item">
			<hr>
			</li>
			<li class="nav-item pl-3 {{request()->is('settings/labs*')?'active':''}}">
				<a class="nav-link font-weight-light text-muted" href="{{route('settings.labs')}}">Labs</a>
			</li>
		</ul>
	</div>

	@push('styles')
	<style type="text/css">
		.settings-nav {
			@media only screen and (min-width: 768px) {
				border-right: 1px solid #dee2e6 !important
			}
		}
	</style>
	@endpush
