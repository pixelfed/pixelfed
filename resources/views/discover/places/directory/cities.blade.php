@extends('layouts.app')

@section('content')
<div class="container mt-5">	
	<div class="col-12">
		<p class="font-weight-bold text-lighter text-uppercase">Cities in {{$places->first()->country}}</p>
		<div class="card border shadow-none">
			<div class="card-body row pl-md-5 ml-md-5">
				@foreach($places as $place)
				<div class="col-12 col-md-4 mb-2">
					<a href="{{$place->cityUrl()}}" class="text-dark pr-3 b-3">{{$place->name}}</a>
				</div>
				@endforeach
			</div>
			<div class="card-footer bg-white pb-0 d-flex justify-content-center">
				{{$places->links()}}
			</div>
		</div>
	</div>
</div>

@endsection