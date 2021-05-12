@extends('admin.partial.template')

@include('admin.settings.sidebar')

@section('section')
<div class="title mb-4">
	<h3 class="font-weight-bold">Settings</h3>
@if(config('instance.enable_cc'))
	<p class="lead mb-0">Manage instance settings.</p>
	<p class="mb-0"><span class="font-weight-bold">Warning</span>: These settings will override .env variables</p>
</div>
<form method="post">
	@csrf
	<ul class="nav nav-tabs nav-fill border-bottom-0" id="myTab" role="tablist">
		<li class="nav-item">
			<a class="nav-link font-weight-bold px-4 active" id="home-tab" data-toggle="tab" href="#home" role="tab" aria-controls="home" aria-selected="true">General</a>
		</li>
		<li class="nav-item border-none">
			<a class="nav-link font-weight-bold px-4" id="media-tab" data-toggle="tab" href="#media" role="tab" aria-controls="media">Media</a>
		</li>
		<li class="nav-item border-none">
			<a class="nav-link font-weight-bold px-4" id="users-tab" data-toggle="tab" href="#users" role="tab" aria-controls="users">Users</a>
		</li>
		<li class="nav-item">
			<a class="nav-link font-weight-bold px-4" id="advanced-tab" data-toggle="tab" href="#advanced" role="tab" aria-controls="advanced">Advanced</a>
		</li>
	</ul>
	<div class="tab-content" id="myTabContent">

	<div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
		<div class="form-group mb-0">
			<div class="ml-n4 mr-n2 p-3 bg-light border-top">
				<label class="font-weight-bold text-muted">Manage Core Features</label>
				<div class="custom-control custom-checkbox mt-2">
					<input type="checkbox" name="activitypub" class="custom-control-input" id="ap" {{config_cache('federation.activitypub.enabled') ? 'checked' : ''}}>
					<label class="custom-control-label font-weight-bold" for="ap">ActivityPub</label>
				</div>
				<div class="custom-control custom-checkbox mt-2">
					<input type="checkbox" name="open_registration" class="custom-control-input" id="openReg" {{config_cache('pixelfed.open_registration') ? 'checked' : ''}}>
					<label class="custom-control-label font-weight-bold" for="openReg">Open Registrations</label>
				</div>
				<div class="custom-control custom-checkbox mt-2">
					<input type="checkbox" name="mobile_apis" class="custom-control-input" id="cf2" {{config_cache('pixelfed.oauth_enabled') ? 'checked' : ''}}>
					<label class="custom-control-label font-weight-bold" for="cf2">Mobile APIs</label>
				</div>
				<div class="custom-control custom-checkbox mt-2">
					<input type="checkbox" name="stories" class="custom-control-input" id="cf3" {{config_cache('instance.stories.enabled') ? 'checked' : ''}}>
					<label class="custom-control-label font-weight-bold" for="cf3">Stories</label>
				</div>
				<div class="custom-control custom-checkbox mt-2">
					<input type="checkbox" name="ig_import" class="custom-control-input" id="cf4" {{config_cache('pixelfed.import.instagram.enabled') ? 'checked' : ''}}>
					<label class="custom-control-label font-weight-bold" for="cf4">Instagram Import</label>
				</div>
				<div class="custom-control custom-checkbox mt-2">
					<input type="checkbox" name="spam_detection" class="custom-control-input" id="cf5" {{config_cache('pixelfed.bouncer.enabled') ? 'checked' : ''}}>
					<label class="custom-control-label font-weight-bold" for="cf5">Spam detection</label>
				</div>
			</div>
		</div>
		<div class="form-group mb-0">
			<div class="ml-n4 mr-n2 p-3 bg-light border-top border-bottom">
				<label class="font-weight-bold text-muted">Name</label>
				<input class="form-control col-8" name="name" placeholder="Pixelfed" value="{{config_cache('app.name')}}">
				<p class="help-text small text-muted mt-3 mb-0">The instance name used in titles, metadata and apis.</p>
			</div>
		</div>
		<div class="form-group mb-0">
			<div class="ml-n4 mr-n2 p-3 bg-light border-bottom">
				<label class="font-weight-bold text-muted">Short Description</label>
				<textarea class="form-control" rows="3" name="short_description">{{config_cache('app.short_description')}}</textarea>
				<p class="help-text small text-muted mt-3 mb-0">Short description of instance used on various pages and apis.</p>
			</div>
		</div>
		<div class="form-group mb-0">
			<div class="ml-n4 mr-n2 p-3 bg-light border-bottom">
				<label class="font-weight-bold text-muted">Long Description</label>
				<textarea class="form-control" rows="3" name="long_description">{{config_cache('app.description')}}</textarea>
				<p class="help-text small text-muted mt-3 mb-0">Longer description of instance used on about page.</p>
			</div>
		</div>
	</div>

	<div class="tab-pane" id="users" role="tabpanel" aria-labelledby="users-tab">
		<div class="form-group mb-0">
			<div class="ml-n4 mr-n2 p-3 bg-light border-top">
				<div class="custom-control custom-checkbox mt-2">
					<input type="checkbox" name="require_email_verification" class="custom-control-input" id="mailVerification" {{config_cache('pixelfed.enforce_email_verification') ? 'checked' : ''}}>
					<label class="custom-control-label font-weight-bold" for="mailVerification">Require Email Verification</label>
				</div>
			</div>
		</div>
		<div class="form-group">
				<div class="ml-n4 mr-n2 p-3 bg-light border-top border-bottom">
					<div class="custom-control custom-checkbox my-2">
						<input type="checkbox" name="enforce_account_limit" class="custom-control-input" id="userEnforceLimit" {{config_cache('pixelfed.enforce_account_limit') ? 'checked' : ''}}>
						<label class="custom-control-label font-weight-bold" for="userEnforceLimit">Enable account storage limit</label>
						<p class="help-text small text-muted">Set a storage limit per user account.</p>
					</div>
					<label class="font-weight-bold text-muted">Account Limit</label>
					<input class="form-control" name="account_limit" placeholder="Pixelfed" value="{{config_cache('pixelfed.max_account_size')}}">
					<p class="help-text small text-muted mt-3 mb-0">Account limit size in KB.</p>
					<p class="help-text small text-muted mb-0">{{config_cache('pixelfed.max_account_size')}} KB = {{floor(config_cache('pixelfed.max_account_size') / 1024)}} MB</p>
				</div>
			</div>
	</div>

	<div class="tab-pane" id="media" role="tabpanel" aria-labelledby="media-tab">
		<div class="form-group mb-0">
			<div class="ml-n4 mr-n2 p-3 bg-light border-top">
				<label class="font-weight-bold text-muted">Max Size</label>
				<input class="form-control" name="max_photo_size" value="{{config_cache('pixelfed.max_photo_size')}}">
				<p class="help-text small text-muted mt-3 mb-0">Maximum file upload size in KB</p>
				<p class="help-text small text-muted mb-0">{{config_cache('pixelfed.max_photo_size')}} KB = {{number_format(config_cache('pixelfed.max_photo_size') / 1024)}} MB</p>
			</div>
		</div>
		<div class="form-group mb-0">
			<div class="ml-n4 mr-n2 p-3 bg-light border-top">
				<label class="font-weight-bold text-muted">Photo Album Limit</label>
				<input class="form-control" name="max_album_length" value="{{config_cache('pixelfed.max_album_length')}}">
				<p class="help-text small text-muted mt-3 mb-0">The maximum number of photos or videos per album</p>
			</div>
		</div>
		<div class="form-group mb-0">
			<div class="ml-n4 mr-n2 p-3 bg-light border-top">
				<label class="font-weight-bold text-muted">Image Quality</label>
				<input class="form-control" name="image_quality" value="{{config_cache('pixelfed.image_quality')}}">
				<p class="help-text small text-muted mt-3 mb-0">Image optimization quality from 0-100%. Set to 0 to disable image optimization.</p>
			</div>
		</div>
		<div class="form-group">
			<div class="ml-n4 mr-n2 p-3 bg-light border-top border-bottom">
				<label class="font-weight-bold text-muted">Media Types</label>
				<div class="custom-control custom-checkbox mt-2">
					<input type="checkbox" name="type_jpeg" class="custom-control-input" id="mediaType1" {{$jpeg ? 'checked' : ''}}>
					<label class="custom-control-label" for="mediaType1">Allow <span class="border border-dark px-1 rounded font-weight-bold">JPEG</span> images (image/jpg)</label>
				</div>
				<div class="custom-control custom-checkbox mt-2">
					<input type="checkbox" name="type_png" class="custom-control-input" id="mediaType2" {{$png ? 'checked' : ''}}>
					<label class="custom-control-label" for="mediaType2">Allow <span class="border border-dark px-1 rounded font-weight-bold">PNG</span> images (image/png)</label>
				</div>
				<div class="custom-control custom-checkbox mt-2">
					<input type="checkbox" name="type_gif" class="custom-control-input" id="mediaType3" {{$gif ? 'checked' : ''}}>
					<label class="custom-control-label" for="mediaType3">Allow <span class="border border-dark px-1 rounded font-weight-bold">GIF</span> images (image/gif)</label>
				</div>
				<div class="custom-control custom-checkbox mt-2">
					<input type="checkbox" name="type_mp4" class="custom-control-input" id="mediaType4" {{$mp4 ? 'checked' : ''}}>
					<label class="custom-control-label" for="mediaType4">Allow <span class="border border-dark px-1 rounded font-weight-bold">MP4</span> video (video/mp4)</label>
				</div>
				<p class="help-text small text-muted mt-3 mb-0">Allowed media types.</p>
			</div>
		</div>
	</div>

	<div class="tab-pane" id="advanced" role="tabpanel" aria-labelledby="advanced-tab">
		<div class="form-group mb-0">
			<div class="ml-n4 mr-n2 p-3 bg-light border-top border-bottom">
				<label class="font-weight-bold text-muted">Custom CSS</label>
				<div class="custom-control custom-checkbox my-2">
					<input type="checkbox" name="show_custom_css" class="custom-control-input" id="showCustomCss" {{config_cache('uikit.show_custom.css') ? 'checked' : ''}}>
					<label class="custom-control-label font-weight-bold" for="showCustomCss">Enable custom CSS</label>
				</div>
				<textarea class="form-control" name="custom_css" rows="3">{{config_cache('uikit.custom.css')}}</textarea>
				<p class="help-text small text-muted mt-3 mb-0">Add custom CSS, will be used on all pages</p>
			</div>
		</div>
	</div>

	</div>

	<div class="form-group row mb-0 mt-4">
		<div class="col-12 text-right">
			<button type="submit" class="btn btn-primary font-weight-bold px-5">Save</button>
		</div>
	</div>
</form>
@else
</div>
<div class="py-5">
	<p class="lead text-center font-weight-bold">Not enabled</p>
	<p class="text-center">Add <code>ENABLE_CONFIG_CACHE=true</code> in your <span class="font-weight-bold">.env</span> file <br /> and run <span class="font-weight-bold">php artisan config:cache</span></p>
</div>
@endif
@endsection
