@extends('layouts.app',['title' => "Account Temporarily Unavailable"])

@section('content')
<div class="container">
	<div class="profile-timeline mt-2 mt-md-4">
		<div class="alert alert-danger">
			<div class="py-2">
				<p class="lead font-weight-bold mb-0">
					{{__('profile.status.disabled.header')}}
				</p>
				<p class="mb-0">
					{{__('profile.status.disabled.body')}}
				</p>
			</div>
		</div>
	</div>
</div>
@endsection