@extends('settings.template')

@section('section')

	<div class="title">
		<h3 class="font-weight-bold">{{__('settings.media')}}</h3>
	</div>
	<hr>
	<form method="post">
		@csrf
		<div class="form-group pb-3">
			<label class="form-check-label font-weight-bold" for="">{{__('settings.defaultLicense')}}</label>
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
			<p class="text-muted small help-text">{{__('settings.defaultLicenseDiscription')}}</p>
		</div>

		<div class="form-check pb-3">
			<input class="form-check-input" type="checkbox" name="sync">
			<label class="form-check-label font-weight-bold" for="">{{__('settings.syncLicenses')}}</label>
			<p class="text-muted small help-text">{!!__('settings.syncLicensesDiscription')!!}</p>
		</div>

		<div class="form-check pb-3">
			<input class="form-check-input" type="checkbox" name="media_descriptions" {{$compose['media_descriptions'] == $license['id'] ? 'checked':''}}>
			<label class="form-check-label font-weight-bold" for="">{{__('settings.requireDescriptions')}}</label>
			<p class="text-muted small help-text">
				{!!__('settings.requireDescriptionsDescription')!!}
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
