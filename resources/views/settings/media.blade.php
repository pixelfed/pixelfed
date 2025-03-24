@extends('settings.template')

@section('section')

	<div class="title">
		<h3 class="font-weight-bold">{{__('settings.media')}}</h3>
	</div>
	<hr>
	<form method="post">
		@csrf
		<div class="form-group pb-3">
			<label class="form-check-label font-weight-bold" for="">{{__('settings.media.default_license')}}</label>
			<select class="form-control" name="default">
				@foreach(App\Util\Media\License::get() as $license)
				<option value="{{$license['id']}}" {{$compose['default_license'] == $license['id'] ? 'selected':''}}>
					{{$license['name']}}
					@if($license['id'] > 10)
					({{$license['title']}})
					@endif
				</option>
				@endforeach
			</select>
			<p class="text-muted small help-text">{{__('settings.media.set_a_default_license_for_new_posts')}}</p>
		</div>

		<div class="form-check pb-3">
			<input class="form-check-input" type="checkbox" name="sync">
			<label class="form-check-label font-weight-bold" for="">{{__('settings.media.sync_licenses')}}</label>
			<p class="text-muted small help-text">{{__('settings.media.update_existing_posts_with_your_new_default_etc')}}<br />{{__('settings.media.license_changes_may_not_be_reflected_on_remote_servers')}}</p>
		</div>

		<div class="form-check pb-3">
			<input class="form-check-input" type="checkbox" name="media_descriptions" {{$compose['media_descriptions'] == $license['id'] ? 'checked':''}}>
			<label class="form-check-label font-weight-bold" for="">{{__('settings.media.require_media_descriptions')}}</label>
			<p class="text-muted small help-text">
				{{__('settings.media.briefly_describe_your_media_to_improve_etc')}}<br />
				<span class="font-weight-bold">{{__('settings.media.not_available_for_mobile_or_3rd_party_apps_etc')}}</span>
			</p>
		</div>

		<div class="form-group row mt-5 pt-5">
			<div class="col-12 text-right">
				<hr>
				<button type="submit" class="btn btn-primary font-weight-bold py-0 px-5">{{__('settings.submit')}}</button>
			</div>
		</div>
	</form>

@endsection
