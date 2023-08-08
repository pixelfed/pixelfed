@extends('settings.template')

@section('section')

	<div class="title">
		<h3 class="font-weight-bold">Account Settings</h3>
	</div>
	<hr>
	<div class="form-group row">
		<div class="col-sm-3">
			<img src="{{Auth::user()->profile->avatarUrl()}}" width="38px" height="38px" class="rounded-circle float-right" draggable="false" onerror="this.src='/storage/avatars/default.jpg?v=0';this.onerror=null;">
		</div>
		<div class="col-sm-9">
			<p class="lead font-weight-bold mb-0">{{Auth::user()->username}}</p>
			<p class="">
				<a href="#" class="font-weight-bold change-profile-photo" data-toggle="collapse" data-target="#avatarCollapse" aria-expanded="false" aria-controls="avatarCollapse">Change Profile Photo</a>
			</p>
			<div class="collapse" id="avatarCollapse">
				<form method="post" action="/settings/avatar" enctype="multipart/form-data">
				@csrf
				<div class="card card-body">
					<div class="custom-file mb-1">
						<input type="file" name="avatar" class="custom-file-input" id="avatarInput">
						<label class="custom-file-label" for="avatarInput">Select a profile photo</label>
					</div>
					<p><span class="small font-weight-bold">Must be a jpeg or png. Max avatar size: <span id="maxAvatarSize"></span></span></p>
					<div id="previewAvatar"></div>
					<p class="mb-0"><button type="submit" class="btn btn-primary px-4 py-0 font-weight-bold">Upload</button></p>
				</div>
				</form>
			</div>
			<p class="">
				<a class="font-weight-bold text-muted delete-profile-photo" href="#">Delete Profile Photo</a>
			</p>
		</div>
	</div>
	<form method="post">
		@csrf
		<div class="form-group row">
			<label for="name" class="col-sm-3 col-form-label font-weight-bold">Name</label>
			<div class="col-sm-9">
				<input type="text" class="form-control" id="name" name="name" placeholder="Your Name" maxlength="30" value="{{Auth::user()->profile->name}}" v-pre>
			</div>
		</div>
		<div class="form-group row">
			<label for="website" class="col-sm-3 col-form-label font-weight-bold">Website</label>
			<div class="col-sm-9">
				<input type="text" class="form-control" id="website" name="website" placeholder="Website" value="{{Auth::user()->profile->website}}" v-pre>
			</div>
		</div>
		<div class="form-group row">
			<label for="bio" class="col-sm-3 col-form-label font-weight-bold">Bio</label>
			<div class="col-sm-9">
				<textarea
					class="form-control"
					id="bio"
					name="bio"
					placeholder="Add a bio here"
					rows="2"
					data-max-length="{{config('pixelfed.max_bio_length')}}"
					maxlength="{{config('pixelfed.max_bio_length')}}"
					v-pre>{{strip_tags(Auth::user()->profile->bio)}}</textarea>
				<p class="form-text">
					<span class="bio-counter float-right small text-muted">0/{{config('pixelfed.max_bio_length')}}</span>
				</p>
			</div>
		</div>
		<div class="form-group row">
			<label for="language" class="col-sm-3 col-form-label font-weight-bold">Language</label>
			<div class="col-sm-9">
				<select class="form-control" name="language">
				@foreach(App\Util\Localization\Localization::languages() as $lang)
					<option value="{{$lang}}" {{(Auth::user()->language ?? 'en') == $lang ? 'selected':''}}>{{locale_get_display_language($lang, 'en')}} - {{locale_get_display_language($lang, $lang)}}</option>
				@endforeach
				</select>
			</div>
		</div>
		<div class="form-group row">
			<label for="pronouns" class="col-sm-3 col-form-label font-weight-bold">Pronouns</label>
			<div class="col-sm-9">
				<select class="form-control" name="pronouns[]" multiple="" id="pronouns">
					<option>Select Pronoun(s)</option>
				@foreach(\App\Services\PronounService::pronouns() as $val)
					<option value="{{$val}}" {{$pronouns && in_array($val, $pronouns) ? 'selected' : ''}}>{{$val}}</option>
				@endforeach
				</select>
				<p class="help-text text-muted small">Select up to 4 pronouns that will appear on your profile.</p>
			</div>
		</div>

        <div class="form-group row">
            <label for="aliases" class="col-sm-3 col-form-label font-weight-bold">Account Aliases</label>
            <div class="col-sm-9" id="aliases">
                <a class="font-weight-bold" href="/settings/account/aliases/manage">Manage account alias</a>
                <p class="help-text text-muted small">To move from another account to this one, first you need to create an alias.</p>
            </div>
        </div>
		@if(config_cache('pixelfed.enforce_account_limit'))
		<div class="pt-3">
			<p class="font-weight-bold text-muted text-center">Storage Usage</p>
		</div>
		<div class="form-group row">
			<label class="col-sm-3 col-form-label font-weight-bold">Storage Used</label>
			<div class="col-sm-9">
				<div class="progress mt-2">
					<div class="progress-bar" role="progressbar" style="width: {{$storage['percentUsed']}}%"  aria-valuenow="{{$storage['percentUsed']}}" aria-valuemin="0" aria-valuemax="100"></div>
				</div>
				<div class="help-text">
					<span class="small text-muted">
						{{$storage['percentUsed']}}% used
					</span>
					<span class="small text-muted float-right">
						{{$storage['usedPretty']}} / {{$storage['limitPretty']}}
					</span>
				</div>
			</div>
		</div>
		@endif
		<hr>
		<div class="form-group row">
			<div class="col-12 text-right">
				<button type="submit" class="btn btn-primary font-weight-bold py-0 px-5">Submit</button>
			</div>
		</div>
	</form>

@endsection

@push('scripts')
<script type="text/javascript">

$(document).ready(function() {
		let el = $('#bio');
		let len = el.val().length;
		let limit = el.data('max-length');

		if(len > 100) {
			el.attr('rows', '4');
		}

		let val = len + ' / ' + limit;

		if(len > limit) {
			let diff = len - limit;
			val = '<span class="text-danger">-' + diff + '</span> / ' + limit;
		}

		$('.bio-counter').html(val);

		$('#bio').on('change keyup paste', function(e) {
			let el = $(this);
			let len = el.val().length;
			let limit = el.data('max-length');

			if(len > 100) {
				el.attr('rows', '4');
			}

			let val = len + ' / ' + limit;

			if(len > limit) {
				let diff = len - limit;
				val = '<span class="text-danger">-' + diff + '</span> / ' + limit;
			}

			$('.bio-counter').html(val);
		});

		$(document).on('click', '.modal-close', function(e) {
			swal.close();
		});

		$('#maxAvatarSize').text(filesize({{config('pixelfed.max_avatar_size') * 1024}}, {round: 0}));

		$('#avatarInput').on('change', function(e) {
				var file = document.getElementById('avatarInput').files[0];
				var reader = new FileReader();

				reader.addEventListener("load", function() {
						$('#previewAvatar').html('<img src="' + reader.result + '" class="rounded-circle box-shadow mb-3" width="100%" height="100%"/>');
				}, false);

				if (file) {
						reader.readAsDataURL(file);
				}
		});

		$('.delete-profile-photo').on('click', function(e) {
			e.preventDefault();
			if(window.confirm('Are you sure you want to delete your profile photo.') == false) {
				return;
			}
			axios.delete('/settings/avatar').then(res => {
				window.location.href = window.location.href;
			}).catch(err => {
				swal('Error', 'An error occured, please try again later', 'error');
			});
		});
})

</script>
@endpush
