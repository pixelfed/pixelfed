@extends('settings.template')

@section('section')

	<div class="title">
		<h3 class="font-weight-bold">{{__('settings.security.timeline_settings')}}</h3>
	</div>
	<hr>
	<form method="post">
		@csrf
		<div class="form-check pb-3 d-none">
			<input class="form-check-input" type="checkbox" name="top" {{$top ? 'checked':''}}>
			<label class="form-check-label font-weight-bold" for="">{{__('settings.security.show_text_only_posts')}}</label>
			<p class="text-muted small help-text">{{__('settings.security.show_text_only_posts_from_accounts_you_follow_home_etc')}}</p>
		</div>
		<div class="form-check pb-3 d-none">
			<input class="form-check-input" type="checkbox" name="replies" {{$replies ? 'checked':''}}>
			<label class="form-check-label font-weight-bold" for="">{{__('settings.security.show_replies')}}</label>
			<p class="text-muted small help-text">{{__('settings.security.show_replies_from_accounts_you_follow_home_timeline_only')}}</p>
		</div>

        <div class="form-check pb-3">
            <input class="form-check-input" type="checkbox" name="enable_reblogs" {{$userSettings['enable_reblogs'] ? 'checked':''}}>
            <label class="form-check-label font-weight-bold" for="">{{__('settings.security.show_reblogs')}}</label>
            <p class="text-muted small help-text">{{__('settings.security.see_reblogs_from_accounts_you_follow_in_your_home_etc')}}</p>
        </div>

        <div class="form-check pb-3">
            <input class="form-check-input" type="checkbox" name="photo_reblogs_only" {{$userSettings['photo_reblogs_only'] ? 'checked':''}}>
            <label class="form-check-label font-weight-bold" for="">{{__('settings.security.photo_reblogs_only')}}</label>
            <p class="text-muted small help-text">{{__('settings.security.only_see_reblogs_of_photos_or_photo_albums_home_etc')}}</p>
        </div>

		<div class="form-group row mt-5 pt-5">
			<div class="col-12 text-right">
				<hr>
				<button type="submit" class="btn btn-primary font-weight-bold py-0 px-5">{{__('settings.submit')}}</button>
			</div>
		</div>
	</form>

@endsection
