<p>{{__('settings.Enabled2faDiscription')}}<a href="#">{{__('settings.leanMore')}}</a>.</p>
<div class="card mb-3">
	<div class="card-header bg-light">
		<span class="font-weight-bold">
			{{__('settings.2faMethods')}}
		</span>
	</div>
	<ul class="list-group list-group-flush">
		<li class="list-group-item">
			<div class="d-flex justify-content-between align-items-center py-2">
				<div>{{__('settings.authenticatorApp')}}</div>
				<div><a class="btn btn-secondary btn-sm font-weight-bold" href="{{route('settings.security.2fa.edit')}}">{{__('settings.edit')}}</a></div>
			</div>
		</li>
	</ul>
</div><div class="card mb-3">
	<div class="card-header bg-light">
		<span class="font-weight-bold">
			{{__('settings.recoveryOptions')}}
		</span>
	</div>
	<ul class="list-group list-group-flush">
		<li class="list-group-item">
			<div class="d-flex justify-content-between align-items-center py-2">
				<div>{{__('settings.recoveryCodes')}}</div>
				<div><a class="btn btn-secondary btn-sm font-weight-bold" href="{{route('settings.security.2fa.recovery')}}">{{__('settings.view')}}</a></div>
			</div>
		</li>
	</ul>
</div>