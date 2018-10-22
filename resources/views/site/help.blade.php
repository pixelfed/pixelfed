@extends('site.partial.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Help</h3>
  </div>
  <hr>
  <div class="row">
    <div class="col-12 col-md-6 mb-3">
      <div class="bg-light p-4">
        <a class="text-muted mb-0 h5 font-weight-bold" href="{{route('help.getting-started')}}">Getting Started</a>
      </div>
    </div>
    <div class="col-12 col-md-6 mb-3">
      <div class="bg-light p-4">
        <a class="text-muted mb-0 h5 font-weight-bold" href="{{route('help.hashtags')}}">Hashtags</a>
      </div>
    </div>
    <div class="col-12 col-md-6 mb-3">
      <div class="bg-light p-4">
        <a class="text-muted mb-0 h5 font-weight-bold" href="{{route('help.sharing-media')}}">Sharing Photos & Videos</a>
      </div>
    </div>
    <div class="col-12 col-md-6 mb-3">
      <div class="bg-light p-4">
        <a class="text-muted mb-0 h5 font-weight-bold" href="{{route('help.discover')}}">Discover</a>
      </div>
    </div>
    <div class="col-12 col-md-6 mb-3">
      <div class="bg-light p-4">
        <a class="text-muted mb-0 h5 font-weight-bold" href="{{route('help.your-profile')}}">Your Profile</a>
      </div>
    </div>
    <div class="col-12 col-md-6 mb-3">
      <div class="bg-light p-4">
        <a class="text-muted mb-0 h5 font-weight-bold" href="{{route('help.dm')}}">Direct Messaging</a>
      </div>
    </div>
    <div class="col-12 col-md-6">
      <div class="bg-light p-4">
        <a class="text-muted mb-0 h5 font-weight-bold" href="{{route('help.stories')}}">Stories</a>
      </div>
    </div>
    <div class="col-12 col-md-6">
      <div class="bg-light p-4">
        <a class="text-muted mb-0 h5 font-weight-bold" href="{{route('help.timelines')}}">Timelines</a>
      </div>
    </div>
  </div>
  <hr>

  <div class="row">

    <div class="col-12 col-md-6 mb-3">
      <div class="bg-light p-4">
        <a class="text-muted mb-0 h5 font-weight-bold" href="{{route('help.community-guidelines')}}">Community Guidelines</a>
      </div>
    </div>
    <div class="col-12 col-md-6 mb-3">
      <div class="bg-light p-4">
        <a class="text-muted mb-0 h5 font-weight-bold" href="{{route('help.blocking-accounts')}}">Blocking accounts</a>
      </div>
    </div>
    <div class="col-12 col-md-6 mb-3">
      <div class="bg-light p-4">
        <a class="text-muted mb-0 h5 font-weight-bold" href="{{route('help.what-is-fediverse')}}">What is the fediverse?</a>
      </div>
    </div>
    <div class="col-12 col-md-6 mb-3">
      <div class="bg-light p-4">
        <a class="text-muted mb-0 h5 font-weight-bold" href="{{route('help.safety-tips')}}">Safety Tips</a>
      </div>
    </div>
    <div class="col-12 col-md-6 mb-3">
      <div class="bg-light p-4">
        <a class="text-muted mb-0 h5 font-weight-bold" href="{{route('help.controlling-visibility')}}">Controlling your visibility</a>
      </div>
    </div>
    <div class="col-12 col-md-6 mb-3">
      <div class="bg-light p-4">
        <a class="text-muted mb-0 h5 font-weight-bold" href="{{route('help.report-something')}}">Report Something</a>
      </div>
    </div>
    <div class="col-12 col-md-6 mb-3">
      <div class="bg-light p-4">
        <a class="text-muted mb-0 h5 font-weight-bold" href="{{route('help.abusive-activity')}}">Abuse/malicious activity</a>
      </div>
    </div>
    <div class="col-12 col-md-6 mb-3">
      <div class="bg-light p-4">
        <a class="text-muted mb-0 h5 font-weight-bold" href="{{route('help.data-policy')}}">Data Policy</a>
      </div>
    </div>
  </div>
  {{-- <div class="card mb-3">
  	<div class="card-body">
  		<p class="h5 font-weight-bold">Using Pixelfed</p>
  		<div class="row font-weight-bold text-muted">
  			<div class="col-12 col-md-6">
  				<ul>
  					<li><a href="{{route('help.getting-started')}}">Getting Started</a></li>

  					<li><a href="{{route('help.sharing-media')}}">Sharing Photos & Videos</a></li>
  					<li><a href="{{route('help.your-profile')}}">Your Profile</a></li>
  					<li><a href="{{route('help.stories')}}">Stories</a></li>
  				</ul>
  			</div>
  			<div class="col-12 col-md-6">
  				<ul>
  					<li><a href="{{route('help.hashtags')}}">Hashtags</a></li>

  					<li><a href="{{route('help.discover')}}">Discover</a></li>
  					<li><a href="{{route('help.dm')}}">Direct Messaging</a></li>
  					<li><a href="{{route('help.timelines')}}">Timelines</a></li>
  				</ul>
  			</div>
  		</div>
  	</div>
  </div>
  <div class="card mb-3">
  	<div class="card-body">
  		<p class="h5 font-weight-bold">Privacy and Safety</p>
  		<div class="row font-weight-bold text-muted">
  			<div class="col-12 col-md-6">
  				<ul>
  					<li><a href="#">Community Guidelines</a></li>
  					<li><a href="#">What is the fediverse?</a></li>
  					<li><a href="#">Controlling your visibility</a></li>
  					<li><a href="#">Abuse/malicious activity</a></li>
  				</ul>
  			</div>
  			<div class="col-12 col-md-6">
  				<ul>
  					<li><a href="#">Blocking accounts</a></li>

  					<li><a href="#">Safety Tips</a></li>
  					<li><a href="#">Report Something</a></li>
  					<li><a href="#">Data Policy</a></li>
  				</ul>
  			</div>
  		</div>
  	</div>
  </div> --}}
@endsection

@push('meta')
<meta property="og:description" content="Help">
@endpush
