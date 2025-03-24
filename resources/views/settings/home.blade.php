@extends('settings.template')

@section('section')

	<div class="title">
		<h3 class="font-weight-bold">{{__('settings.home.account_settings')}}</h3>
	</div>
	<hr>
	<div class="form-group row">
		<div class="col-sm-3">
			<img src="{{Auth::user()->profile->avatarUrl()}}" width="38px" height="38px" class="rounded-circle float-right" draggable="false" onerror="this.src='/storage/avatars/default.jpg?v=0';this.onerror=null;">
		</div>
		<div class="col-sm-9">
			<p class="lead font-weight-bold mb-0">{{Auth::user()->username}}</p>
			<p class="">
				<a href="#" class="font-weight-bold change-profile-photo" data-toggle="collapse" data-target="#avatarCollapse" aria-expanded="false" aria-controls="avatarCollapse">{{__('settings.home.change_profile_photo')}}</a>
			</p>
			<div class="collapse" id="avatarCollapse">
				<form method="post" action="/settings/avatar" enctype="multipart/form-data">
				@csrf
				<div class="card card-body">
					<div class="custom-file mb-1">
						<input type="file" name="avatar" class="custom-file-input" id="avatarInput">
						<label class="custom-file-label" for="avatarInput">{{__('settings.home.select_a_profile_photo')}}</label>
					</div>
					<p><span class="small font-weight-bold">{{__('settings.home.must_be_a_jpeg_or_png_max_avatar_size')}} <span id="maxAvatarSize"></span></span></p>
					<div id="previewAvatar"></div>
					<p class="mb-0"><button type="submit" class="btn btn-primary px-4 py-0 font-weight-bold">{{__('settings.home.upload')}}</button></p>
				</div>
				</form>
			</div>
			<p class="">
				<a class="font-weight-bold text-muted delete-profile-photo" href="#">{{__('settings.home.delete_profile_photo')}}</a>
			</p>
		</div>
	</div>
	<form method="post">
		@csrf
		<div class="form-group row">
			<label for="name" class="col-sm-3 col-form-label font-weight-bold">{{__('settings.home.name')}}</label>
			<div class="col-sm-9">
				<input type="text" class="form-control" id="name" name="name" placeholder="{{__('settings.home.your_name')}}" maxlength="30" value="{{Auth::user()->profile->name}}" v-pre>
			</div>
		</div>
		<div class="form-group row">
			<label for="website" class="col-sm-3 col-form-label font-weight-bold">{{__('settings.home.website')}}</label>
			<div class="col-sm-9">
				<input type="text" class="form-control" id="website" name="website" placeholder="{{__('settings.home.website')}}" value="{{Auth::user()->profile->website}}" v-pre>
			</div>
		</div>
		<div class="form-group row">
			<label for="bio" class="col-sm-3 col-form-label font-weight-bold">{{__('settings.home.bio')}}</label>
			<div class="col-sm-9">
				<textarea
					class="form-control"
					id="bio"
					name="bio"
					placeholder="{{__('settings.home.add_a_bio_here')}}"
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
			<label for="language" class="col-sm-3 col-form-label font-weight-bold">{{__('settings.home.language')}}</label>
			<div class="col-sm-9">
				<select class="form-control" name="language">
				@foreach(App\Util\Localization\Localization::languages() as $lang)
					<option value="{{$lang}}" {{(Auth::user()->language ?? 'en') == $lang ? 'selected':''}}>{{locale_get_display_language($lang, 'en')}} - {{locale_get_display_language($lang, $lang)}}</option>
				@endforeach
				</select>
			</div>
		</div>
		<div class="form-group row">
			<label for="pronouns" class="col-sm-3 col-form-label font-weight-bold">{{__('settings.home.pronouns')}}</label>
			<div class="col-sm-9">
				<select class="form-control" name="pronouns[]" multiple="" id="pronouns">
					<option>{{__('settings.home.select_pronouns')}}</option>
				@foreach(\App\Services\PronounService::pronouns() as $val)
					<option value="{{$val}}" {{$pronouns && in_array($val, $pronouns) ? 'selected' : ''}}>{{$val}}</option>
				@endforeach
				</select>
				<p class="help-text text-muted small">{{__('settings.home.select_up_to_4_pronouns_that_will_appear_on_etc')}}</p>
			</div>
		</div>

        @if((bool) config_cache('federation.activitypub.enabled'))
        <div class="form-group row">
            <label for="aliases" class="col-sm-3 col-form-label font-weight-bold">{{__('settings.home.account_aliases')}}</label>
            <div class="col-sm-9" id="aliases">
                <a class="font-weight-bold" href="/settings/account/aliases/manage">{{__('settings.home.manage_account_alias')}}</a>
                <p class="help-text text-muted small">{{__('settings.home.to_move_from_another_account_to_this_one_first_etc')}}</p>
            </div>
        </div>

        @if((bool) config_cache('federation.migration'))
        <div class="form-group row">
            <label for="aliases" class="col-sm-3 col-form-label font-weight-bold">{{__('settings.home.account_migrate')}}</label>
            <div class="col-sm-9" id="aliases">
                <a class="font-weight-bold" href="/settings/account/migration/manage">{{__('settings.home.migrate_to_another_account')}}</a>
                <p class="help-text text-muted small">{{__('settings.home.to_redirect_this_account_to_a_different_one_etc')}}</p>
            </div>
        </div>
        @endif
        @endif
		@if(config_cache('pixelfed.enforce_account_limit'))
		<div class="pt-3">
			<p class="font-weight-bold text-muted text-center">{{__('settings.home.storage_usage')}}</p>
		</div>
		<div class="form-group row">
			<label class="col-sm-3 col-form-label font-weight-bold">{{__('settings.home.storage_used')}}</label>
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
				<button type="submit" class="btn btn-primary font-weight-bold py-0 px-5">{{__('settings.submit')}}</button>
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
			if(window.confirm('{{__('settings.home.are_you_sure_you_want_to_delete_your_profile_photo')}}') == false) {
				return;
			}
			axios.delete('/settings/avatar').then(res => {
				window.location.href = window.location.href;
			}).catch(err => {
				swal('{{__('settings.error')}}', '{{__('settings.home.an_error_occured_please_try_again_later')}}', 'error');
			});
		});
})

</script>
@endpush
