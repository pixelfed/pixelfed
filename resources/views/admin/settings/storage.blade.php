@extends('admin.partial.template')

@section('section')
  <div class="title">
    <h3 class="font-weight-bold">Storage</h3>
  </div>
  <hr>

  <div class="card">
  	<div class="card-body">
		<div class="progress">
		  <div class="progress-bar" role="progressbar" style="width: {{$storage->percentUsed}}%" aria-valuenow="{{$storage->percentUsed}}" aria-valuemin="0" aria-valuemax="100"></div>
		</div>
		<div class="d-flex justify-content-between">
			<span class="font-weight-bold">
			 Used: {{$storage->prettyTotal}}
			</span>
			<span class="font-weight-bold">
			{{$storage->percentUsed}}% Used	
			</span>
			<span class="font-weight-bold">
			  Free: {{$storage->prettyFree}}
			</span>
		</div>
  	</div>
  	<div class="card-footer bg-white font-weight-bold text-center">
  		Total Disk Space
  	</div>
  </div>
@endsection