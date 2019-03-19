@extends('site.help.partial.template', ['breadcrumb'=>'Safety Tips'])

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Safety Tips</h3>
  </div>
  <hr>
{{--   <div class="card mb-3">
  	<div class="card-body">
  		<div class="row">
  			<div class="col-12 col-md-3 text-center">
  				<div class="icon-wrapper">
  					<i class="fas fa-exclamation-circle fa-3x text-light"></i>
  				</div>
  			</div>
  			<div class="col-12 col-md-9 d-flex align-items-center">
  				<div class="text-center">
	  				<p class="h3 font-weight-bold mb-0">Work In Progress</p>
	  				<p class="font-weight-light mb-0">We haven't finished it yet, it will be updated soon!</p>
  				</div>
  			</div>
  		</div>
  	</div>
  </div>
 --}}
  <p class="lead py-4">We are committed to building a fun, easy to use photo sharing platform that is safe and secure for everyone.</p>

  <div class="card border-left-blue mb-3">
    <div class="card-body">
      <p class="h5">Know the rules</p>
      <p class="mb-0">To keep yourself safe, it is important to know the <a href="{{route('site.terms')}}">terms of service</a> rules.</p>
    </div>
  </div>

  <div class="card border-left-blue mb-3">
    <div class="card-body">
      <p class="h5">Know the age guidelines</p>
      <p class="mb-0">Please keep in mind that Pixelfed is meant for people over the age of 16 or 13 depending on where you live.</p>
    </div>
  </div>

  <div class="card border-left-blue mb-3">
    <div class="card-body">
      <p class="h5">Report problematic content</p>
      <p class="mb-0">You can report content that you think is in violation of our policies.</p>
    </div>
  </div>

  <div class="card border-left-blue mb-3">
    <div class="card-body">
      <p class="h5">Understanding content visibility</p>
      <p class="mb-0">You can limit the visibility of your content to specific people, followers, public and more.</p>
    </div>
  </div>


  <div class="card border-left-blue mb-3">
    <div class="card-body">
      <p class="h5">Make your account or posts private</p>
      <p class="mb-0">You can make your account private and vet new follow requests to control who your posts are shared with.</p>
    </div>
  </div>
@endsection
