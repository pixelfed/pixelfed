@extends('admin.partial.template')

@section('section')
  <div class="title">
    <h3 class="font-weight-bold">System</h3>
  </div>
  <hr>
  <div class="row">
  	<div class="col-12 col-md-6">
  		<div class="card mb-3">
  			<div class="card-body text-center">
  				<p class="font-weight-ultralight display-4 mb-0">{{config('pixelfed.version')}}</p>
  			</div>
  			<div class="card-footer font-weight-bold text-center bg-white">Pixelfed</div>
  		</div>
	
  		<div class="card mb-3">
  			<div class="card-body text-center">
  				<p class="font-weight-ultralight display-4 mb-0">{{DB::select( DB::raw("select version()") )[0]->{'version()'} }}</p>
  			</div>
  			<div class="card-footer font-weight-bold text-center bg-white">MySQL</div>
  		</div>
  	</div>
  	<div class="col-12 col-md-6">
  		<div class="card mb-3">
  			<div class="card-body text-center">
  				<p class="font-weight-ultralight display-4 mb-0">{{phpversion()}}</p>
  			</div>
  			<div class="card-footer font-weight-bold text-center bg-white">PHP</div>
  		</div>  	
{{--   		<div class="card mb-3">
  			<div class="card-body text-center">
  				<p class="font-weight-ultralight display-4 mb-0"></p>
  			</div>
  			<div class="card-footer font-weight-bold text-center bg-white">Redis</div>
  		</div> --}}
  	</div>
  </div>
@endsection