@extends('admin.partial.template')

@section('section')
  <div class="title">
    <h3 class="font-weight-bold">System</h3>
  </div>
  <hr>
  <div class="row">
  	<div class="col-12 col-md-6">
  		<div class="card mb-3 border-left-blue">
  			<div class="card-body text-center">
  				<p class="font-weight-ultralight display-4 mb-0">{{$sys['pixelfed']}}</p>
  			</div>
  			<div class="card-footer font-weight-bold text-center">Pixelfed</div>
  		</div>
	
  		<div class="card mb-3 border-left-blue">
  			<div class="card-body text-center">
  				<p class="font-weight-ultralight display-4 mb-0">{{$sys['mysql']}}</p>
  			</div>
  			<div class="card-footer font-weight-bold text-center">MySQL</div>
  		</div>
  	</div>
  	<div class="col-12 col-md-6">
  		<div class="card mb-3 border-left-blue">
  			<div class="card-body text-center">
  				<p class="font-weight-ultralight display-4 mb-0">{{$sys['php']}}</p>
  			</div>
  			<div class="card-footer font-weight-bold text-center">PHP</div>
  		</div>  	
  		<div class="card mb-3 border-left-blue">
  			<div class="card-body text-center">
  				<p class="font-weight-ultralight display-4 mb-0">{{$sys['redis']}}</p>
  			</div>
  			<div class="card-footer font-weight-bold text-center">Redis</div>
  		</div>
  	</div>
  </div>
@endsection
