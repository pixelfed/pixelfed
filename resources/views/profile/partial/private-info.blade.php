<div class="bg-white py-5 border-bottom">
	<div class="container">
		<div class="row">
			<div class="col-12 col-md-4 d-flex">
				<div class="profile-avatar mx-auto">
					<img class="rounded-circle box-shadow" src="{{$user->avatarUrl()}}" width="172px" height="172px">
				</div>
			</div>
			<div class="col-12 col-md-8 d-flex align-items-center">
				<div class="profile-details">
					<div class="username-bar pb-2 d-flex align-items-center">
						<span class="font-weight-ultralight h3">{{$user->username}}</span>
						@if(Auth::check() && $is_following == true)
						<span class="pl-4">
							<form class="follow-form" method="post" action="/i/follow" style="display: inline;" data-id="{{$user->id}}" data-action="unfollow">
								@csrf
								<input type="hidden" name="item" value="{{$user->id}}">
								<button class="btn btn-outline-secondary font-weight-bold px-4 py-0" type="submit">Unfollow</button>
							</form>
						</span>
						@elseif(Auth::check() && $requested == true)
						<span class="pl-4">
							<button class="btn btn-outline-secondary font-weight-bold px-4 py-0 disabled" disabled type="button">Follow Requested</button>
						</span>
						@elseif(Auth::check() && $is_following == false)
						<span class="pl-4">
							<form class="follow-form" method="post" action="/i/follow" style="display: inline;" data-id="{{$user->id}}" data-action="follow">
								@csrf
								<input type="hidden" name="item" value="{{$user->id}}">
								<button class="btn btn-primary font-weight-bold px-4 py-0" type="submit">Follow</button>
							</form>
						</span>
						@endif
						@auth
						<span class="pl-4">
							<i class="fas fa-cog fa-lg text-muted cursor-pointer" data-toggle="modal" data-target="#ctxProfileMenu"></i>
							<div class="modal" tabindex="-1" role="dialog" id="ctxProfileMenu">
								<div class="modal-dialog modal-dialog-centered modal-sm">
									<div class="modal-content">
										<div class="modal-body p-0">
											<div class="list-group-item cursor-pointer text-center rounded text-dark" onclick="window.App.util.clipboard('{{$user->url()}}');$('#ctxProfileMenu').modal('hide')">
												Copy Link
											</div>
											@auth
											<div class="list-group-item cursor-pointer text-center rounded text-dark" onclick="muteProfile()">
												Mute
											</div>
											<a class="list-group-item cursor-pointer text-center rounded text-dark text-decoration-none" href="i/report?type=user&id={{$user->id}}">
												Report User
											</a>
											<div class="list-group-item cursor-pointer text-center rounded text-dark" onclick="blockProfile()">
												Block
											</div>
											@endauth
											<div class="list-group-item cursor-pointer text-center rounded text-muted" onclick="$('#ctxProfileMenu').modal('hide')">
												Close
											</div>
										</div>
									</div>
								</div>
							</div>
						</span>
						@endauth
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

@push('scripts')
@auth
<script type="text/javascript">
	function muteProfile() {
			axios.post('/i/mute', {
				type: 'user',
				item: '{{$user->id}}'
			}).then(res => {
				$('#ctxProfileMenu').modal('hide');
				$('#ctxProfileMenu').hide();
				swal('Muted Profile', 'You have successfully muted this profile.', 'success');
			});
	}
	function blockProfile() {
			axios.post('/i/block', {
				type: 'user',
				item: '{{$user->id}}'
			}).then(res => {
				$('#ctxProfileMenu').modal('hide');
				$('#ctxProfileMenu').hide();
				swal('Blocked Profile', 'You have successfully blocked this profile.', 'success');
			});
	}

</script>
@endauth
@endpush
