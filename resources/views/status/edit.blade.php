@extends('layouts.app')

@section('content')

<div class="container">
	<div class="col-12 col-md-8 offset-md-2 pt-4">

		<div class="card shadow-none border">
			<div class="card-header bg-white font-weight-bold d-flex justify-content-between align-items-center">
				<span>{{__('status.editStatus')}}</span>
				<a class="btn btn-outline-primary btn-sm font-weight-bold" href="{{$status->url()}}">{{__('status.backPost')}}</a>
			</div>
			<div class="card-body">
				<form method="post">
					@csrf
					<div class="form-group">
						<label class="font-weight-bold text-muted small">{{__('status.license')}}</label>
						<select class="form-control" name="license">
							@foreach($licenses as $license)
							<option value="{{$license['id']}}" {{$status->firstMedia()->license == $license['id'] ? 'selected' : ''}}>{{$license['title']}}</option>
							@endforeach
						</select>
					</div>
					<hr>
					<button class="btn btn-primary btn-block font-weight-bold">{{__('status.save')}}</button>
				</form>
			</div>
		</div>
	</div>
</div>
@endsection