@extends('admin.partial.template-full')

@section('section')
  <div class="title">
    <h3 class="font-weight-bold">Dashboard</h3>
  </div>

  <hr>

  <div class="row">
    <div class="col-md-4">
      <div class="card shadow-none border" style="min-height:125px">
        <div class="card-body">
          <p class="small text-uppercase font-weight-bold text-muted">New Messages</p>
          <p class="h2 mb-0">{{$data['contact']}}</p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-none border" style="min-height:125px">
        <div class="card-body">
          <p class="small text-uppercase font-weight-bold text-muted">Failed Jobs (24h)</p>
          <p class="h2 mb-0">{{$data['failedjobs']}}</p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-none border" style="min-height:125px">
        <div class="card-body">
          <p class="small text-uppercase font-weight-bold text-muted">Reports</p>
          <p class="h2 mb-0" title="{{$data['reports']}}" data-toggle="tooltip">{{$data['reports']}}</p>
        </div>
      </div>
    </div>
  </div>

  <div class="row mt-4">
    <div class="col-md-4">
      <div class="card shadow-none border" style="min-height:125px">
        <div class="card-body">
          <p class="small text-uppercase font-weight-bold text-muted">Statuses</p>
          <p class="h2 mb-0" title="{{$data['statuses']}}" data-toggle="tooltip">{{$data['statuses']}}</p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-none border" style="min-height:125px">
        <div class="card-body">
          <p class="small text-uppercase font-weight-bold text-muted">Profiles</p>
          <p class="h2 mb-0">{{$data['profiles']}}</p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-none border" style="min-height:125px">
        <div class="card-body">
          <p class="small text-uppercase font-weight-bold text-muted">Users</p>
          <p class="h2 mb-0">{{$data['users']}}</p>
        </div>
      </div>
    </div>
  </div>

  <div class="row mt-4">
    <div class="col-md-4">
      <div class="card shadow-none border" style="min-height:125px">
        <div class="card-body">
          <p class="small text-uppercase font-weight-bold text-muted">Remote Instances</p>
          <p class="h2 mb-0">{{$data['instances']}}</p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-none border" style="min-height:125px">
        <div class="card-body">
          <p class="small text-uppercase font-weight-bold text-muted">Photos Uploaded</p>
          <p class="h2 mb-0">{{$data['media']}}</p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-none border" style="min-height:125px">
        <div class="card-body">
          <p class="small text-uppercase font-weight-bold text-muted">Storage Used</p>
          <p class="human-size mb-0" data-bytes="{{$data['storage']}}">{{$data['storage']}} bytes</p>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script type="text/javascript">
    $(document).ready(function() {
      $('.human-size').each(function(d,a) {
        let el = $(a);
        let size = el.data('bytes');
        el.addClass('h2');
        el.text(filesize(size, {round: 0}));
      });
    });
  </script>
@endpush