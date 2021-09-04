<ul class="list-group">
	<li class="list-group-item bg-light">
		<div class="text-center py-5 px-4">
			<p class="text-muted">
				<i class="fas fa-lock fa-2x"></i>
			</p>
			<p class="text-muted h4 font-weight-bold">
				{{__('settings.notEnabled2fa')}}
			</p>
			<p class="text-muted">
				{!!__('settings.Enabled2faDiscription',['url'=>'#'])!!}
			</p>
			<p class="mb-0">
				<a class="btn btn-success font-weight-bold" href="{{route('settings.security.2fa.setup')}}">{{__('settings.enable2fa')}}</a>
			</p>
		</div>
	</li>
</ul>