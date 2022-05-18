@extends('layouts.app')

@section('content')
<div class="container">
  <div class="col-12">
    <div class="card shadow-none border mt-5">
      <div class="card-body">
        <div class="row">
          <div class="col-12 p-3 p-md-5">
			  <div class="title">
			    <h3 class="font-weight-bold">Edit Two-Factor Authentication</h3>
			  </div>

			  <hr>

			  <p class="lead pb-3">
			  	To register a new device, you have to remove any active devices.
			  </p>

			  <div class="card">
			  	<div class="card-header bg-light font-weight-bold">
			  		Authenticator App
			  	</div>
			  	<div class="card-body d-flex justify-content-between align-items-center">
			  		<i class="fas fa-lock fa-3x text-success"></i>
			  		<p class="font-weight-bold mb-0">
			  			Added {{$user->{'2fa_setup_at'}->diffForHumans()}}
			  		</p>
			  	</div>
			  	<div class="card-footer bg-white text-right">
			  		<a class="btn btn-outline-secondary btn-sm px-4 font-weight-bold mr-3" href="{{route('settings.security.2fa.recovery')}}">View Recovery Codes</a>
			  		<a class="btn btn-outline-danger btn-sm px-4 font-weight-bold remove-device" href="#">Remove</a>
			  	</div>
			  </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {

	$(document).on('click', '.remove-device', function(e) {
		e.preventDefault();
		swal({
			title: 'Confirm Device Removal',
			text: 'Are you sure you want to remove this two-factor authentication device from your account?',
			icon: 'warning',
			button: {
				text: 'Confirm Removal',
				className: 'btn-danger'
			}
		})
		.then((value) => {
			if(value == true) {
				swal({
					title: 'Are you really sure?',
					text: 'Are you really sure you want to remove this two-factor authentication device from your account?',
					icon: 'warning',
					button: {
						text: 'Confirm Removal',
						className: 'btn-danger'
					}
				})
				.then((value) => {
					if(value == true) {
						axios.post('/settings/security/2fa/edit', {
							action: 'remove'
						})
						.then(function(res) {
							window.location.href = '/settings/security';
						})
						.catch(function(res) {
							swal(
								'Oops!',
								'Something went wrong. Please try again.',
								'error'
							);
						})
					}
				});
			}
		});
	});
});

</script>
@endpush
