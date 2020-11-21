<p>Two-factor authentication adds an additional layer of security to your account by requiring more than just a password to log in. <a href="#">Learn more</a>.</p>
<div class="card mb-3">
	<div class="card-header bg-light">
		<span class="font-weight-bold">
			Two-factor methods
		</span>
	</div>
	<ul class="list-group list-group-flush">
		<li class="list-group-item">
			<div class="d-flex justify-content-between align-items-center py-2">
				<div>Authenticator App</div>
				<div><a class="btn btn-secondary btn-sm font-weight-bold" href="{{route('settings.security.2fa.edit')}}">Edit</a></div>
			</div>
		</li>
	</ul>
</div><div class="card mb-3">
	<div class="card-header bg-light">
		<span class="font-weight-bold">
			Recovery Options
		</span>
	</div>
	<ul class="list-group list-group-flush">
		<li class="list-group-item">
			<div class="d-flex justify-content-between align-items-center py-2">
				<div>Recovery Codes</div>
				<div><a class="btn btn-secondary btn-sm font-weight-bold" href="{{route('settings.security.2fa.recovery')}}">View</a></div>
			</div>
		</li>
	</ul>
</div>