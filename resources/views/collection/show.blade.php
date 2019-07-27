@extends('layouts.app')

@section('content')

<div class="container">
	<div class="row">
		<div class="col-12 mt-5 py-5">
			<div class="text-center">
				<h1>Collection</h1>
				<h4 class="text-muted">{{$collection->title}}</h4>
				@auth
				@if($collection->profile_id == Auth::user()->profile_id)
				<div class="text-right">
				<form method="post" action="/api/local/collection/{{$collection->id}}">
					@csrf
					<input type="hidden" name="_method" value="DELETE">
					<button type="submit" class="btn btn-outline-danger font-weight-bold btn-sm py-1">Delete</button>
				</form>
				</div>
				@endif
				@endauth
			</div>
		</div>
		<div class="col-12">
			<collection-component collection-id="{{$collection->id}}"></collection-component>
		</div>
	</div>
</div>

@endsection

@push('styles')
<style type="text/css">
</style>
@endpush
@push('scripts')
<script type="text/javascript" src="{{mix('js/compose.js')}}"></script>
<script type="text/javascript" src="{{mix('js/collections.js')}}"></script>
<script type="text/javascript">
	new Vue({
		el: '#content'
	})
</script>
@endpush	