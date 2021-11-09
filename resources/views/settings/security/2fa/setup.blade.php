@extends('layouts.app')

@section('content')
<div class="container">
  <div class="col-12">
    <div class="card shadow-none border mt-5">
      <div class="card-body">
        <div class="row">
          <div class="col-12 p-3 p-md-5">
			  <div class="title">
			    <h3 class="font-weight-bold">Setup Two-Factor Authentication</h3>
			  </div>
			  <hr>
			  <div class="alert alert-info font-weight-light mb-3">
			  	We only support Two-Factor Authentication via TOTP mobile apps.
			  </div>
			  <section class="step-one pb-5">
			  	<div class="sub-title font-weight-bold h5" data-toggle="collapse" data-target="#step1" aria-expanded="true" aria-controls="step1" data-step="1">
			  		Step 1: Install compatible 2FA mobile app <i class="float-right fas fa-chevron-down"></i>
			  	</div>
			  	<hr>
			  	<div class="collapse show" id="step1">
				  	<p>You will need to install a compatible mobile app, we recommend the following apps:</p>
				  	<ul>
				  		<li><a href="https://1password.com/downloads/" rel="nooopener nofollow">1Password</a></li>
				  		<li><a href="https://authy.com/download/" rel="nooopener nofollow">Authy</a></li>
				  		<li><a href="https://lastpass.com/auth/" rel="nooopener nofollow">LastPass Authenticator</a></li>
				  		<li>
				  			Google Authenticator
				  			<a class="small" href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=en_CA" rel="nooopener nofollow">
				  				(android)
				  			</a>
				  			<a class="small" href="https://itunes.apple.com/ca/app/google-authenticator/id388497605?mt=8" rel="nooopener nofollow">
				  				(iOS)
				  			</a>
				  		</li>
				  		<li><a href="https://www.microsoft.com/en-us/account/authenticator" rel="nooopener nofollow">Microsoft Authenticator</a></li>
				  	</ul>
			  	</div>
			  </section>

			  <section class="step-two pb-5">
			  	<div class="sub-title font-weight-bold h5" data-toggle="collapse" data-target="#step2" aria-expanded="false" aria-controls="step2" data-step="2">
			  		Step 2: Scan QR Code and confirm <i class="float-right fas fa-chevron-down"></i>
			  	</div>
			  	<hr>
			  	<div class="collapse" id="step2">
				  	<p>Please scan the QR code and then enter the 6 digit code in the form below. Keep in mind the code changes every 30 seconds, and is only good for 1 minute.</p>
				  	<div class="card">
				  		<div class="card-body text-center">
				  			<div class="pb-3">
				  				<p class="font-weight-bold">QR Code</p>
				  				<img src="data:image/png;base64,{{$qrcode}}" class="img-fluid" width="200px">
				  			</div>
				  			<div>
				  				<p class="font-weight-bold">OTP Secret</p>
				  				<input type="text" class="form-control" value="{{ $user->{'2fa_secret'} }}" disabled>
				  			</div>
				  		</div>
				  		<div class="card-body">
				  			<form id="confirm-code">
					  			<div class="form-group">
					  				<label class="font-weight-bold small">Code</label>
					  				<input type="text" name="code" id="verifyCode" class="form-control" placeholder="Code" autocomplete="off">
					  			</div>
					  			<button type="submit" class="btn btn-primary font-weight-bold">Submit</button>
				  			</form>
				  		</div>
				  	</div>
			  	</div>
			  </section>

			  <section class="step-three pb-5">
			  	<div class="sub-title font-weight-bold h5" data-toggle="collapse" data-target="#step3" aria-expanded="true" aria-controls="step3" data-step="3">
			  		Step 3: Download Backup Codes <i class="float-right fas fa-chevron-down"></i>
			  	</div>
			  	<hr>
			  	<div class="collapse" id="step3">
				  	<p>Please store the following codes in a safe place, each backup code can be used only once if you do not have access to your 2FA mobile app.</p>

				  	<code>
				  	@foreach($backups as $code)
				  	<p class="mb-0">{{$code}}</p>
				  	@endforeach
				  	</code>
			  	</div>
			  </section>

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
	$('#step3').addClass('d-none');
	window.twoFactor = {};
	window.twoFactor.validated = false;

	$(document).on('click', 'div[data-toggle=collapse]', function(e) {
		let el = $(this);
		let step = el.data('step');

		switch(step) {
			case 1:
				$('#step2').collapse('hide');
				$('#step3').collapse('hide');
			break;
			case 2:
				$('#step1').collapse('hide');
				$('#step3').collapse('hide');
			break;
			case 3:
				if(twoFactor.validated == false) {
					e.preventDefault();
					return;
				} else {
					$('#step3').removeClass('d-none');
					$('#step1').collapse('hide');
					$('#step2').collapse('hide');
				}
			break;
		}
	});

	$(document).on('submit', '#confirm-code', function(e) {
		e.preventDefault();
		let el = $(this);
		let code = $('#verifyCode').val();
		if(code.length < 5) {
			swal('Oops!', 'You need to enter a valid code', 'error');
			return;
		}
		axios.post(window.location.href, {
			code: code
		}).then((res) => {
			twoFactor.validated = true;
			$('#step3').removeClass('d-none');
			$('#step3').collapse('show');
			$('#step1').collapse('hide');
			$('#step2').collapse('hide');
		}).catch((res) => {
			swal('Oops!', 'That was an invalid code, please try again.', 'error');
			return;
		});
	});
});
</script>
@endpush
