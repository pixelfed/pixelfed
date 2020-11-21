@extends('admin.partial.template')

@include('admin.settings.sidebar')

@section('section')
  <div class="title">
    <h3 class="font-weight-bold">System</h3>
    <p class="lead">System information</p>
  </div>
  <hr>
  <p class="h6 text-uppercase text-center">STACK</p>
  <div class="row">
  	<div class="col-12 col-md-3">
  		<div class="card mb-3 border-left-blue">
  			<div class="card-body text-center">
  				<p class="font-weight-ultralight h2 mb-0 text-truncate" title="{{$sys['pixelfed']}}" data-toggle="tooltip">{{$sys['pixelfed']}}</p>
  			</div>
  			<div class="card-footer font-weight-bold py-0 text-center bg-white">Pixelfed</div>
      </div>
  	</div>
    <div class="col-12 col-md-3">
      <div class="card mb-3 border-left-blue">
        <div class="card-body text-center">
          <p class="font-weight-ultralight h2 mb-0 text-truncate" title="{{$sys['database']['version']}}" data-toggle="tooltip">{{$sys['database']['version']}}</p>
        </div>
        <div class="card-footer font-weight-bold py-0 text-center bg-white">{{$sys['database']['name']}}</div>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="card mb-3 border-left-blue">
        <div class="card-body text-center">
          <p class="font-weight-ultralight h2 mb-0 text-truncate" title="{{$sys['php']}}" data-toggle="tooltip">{{$sys['php']}}</p>
        </div>
        <div class="card-footer font-weight-bold py-0 text-center bg-white">PHP</div>
      </div>
    </div>
  	<div class="col-12 col-md-3">
  		<div class="card mb-3 border-left-blue">
  			<div class="card-body text-center">
  				<p class="font-weight-ultralight h2 mb-0 text-truncate" title="{{$sys['laravel']}}" data-toggle="tooltip">{{$sys['laravel']}}</p>
  			</div>
  			<div class="card-footer font-weight-bold py-0 text-center bg-white">Laravel</div>
  		</div>
  	</div>
  </div>
  <hr>
  <p class="h6 text-uppercase text-center">LATEST RELEASE</p>
  <div>
    <div class="card-loading text-center">
      <div class="spinner-border" role="status">
        <span class="sr-only">Loading…</span>
      </div>
    </div>
    <div class="card card-release d-none">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <p class="h1 latest-version mb-0 mr-4">0.0.0</p>
          </div>
          <div class="text-left px-3">
            <p class="mb-0 latest-name h2 font-weight-bold"></p>
            <p class="mb-0 latest-branch badge badge-primary"></p>
            <p class="mb-0 latest-body"></p>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('styles')
<style type="text/css">
  .latest-body h1 {
    font-size: 1.3rem;
  }

  .latest-body h2 {
    font-size: 1.2rem;
  }

  .latest-body h3 {
    font-size: 1.0rem;
  }

  .latest-branch {
    font-size: 0.8rem;
  }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/marked/0.6.0/marked.min.js" integrity="sha256-Z0oIr+NZFYgsP19IS8I9OHioGTr34whIUMpSNMaKj8o=" crossorigin="anonymous"></script>
<script type="text/javascript">
$(document).ready(function() {

  function latestRelease() {
    let api = 'https://api.github.com/repos/pixelfed/pixelfed/releases';
    delete window.axios.defaults.headers.common['X-CSRF-TOKEN'];
    axios.get(api)
      .then(res => {
        let latest = res.data[0];
        $('.latest-version').text(latest.tag_name);
        $('.latest-name').text(latest.name);
        $('.latest-branch').text(latest.target_commitish);
        $('.latest-body').html(marked(latest.body));
        $('.card-loading').hide();
        $('.card-release').removeClass('d-none');
      });
  }

  latestRelease();

});
</script>
@endpush
