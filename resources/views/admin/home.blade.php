@extends('admin.partial.template')

@section('section')
  <div class="title">
    <h3 class="font-weight-bold">Dashboard</h3>
  </div>

  <hr>

  <div class="alert alert-info">
    Hello, <b>{{Auth::user()->name}}</b>
  </div>

  <div class="row">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body text-center">
          <p class="h2">0</p>
          <p class="small text-uppercase font-weight-bold text-muted mb-0">Alerts</p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card">
        <div class="card-body text-center">
          <p class="h2">{{App\Util\Lexer\PrettyNumber::convert(App\Status::whereLocal(true)->count())}}</p>
          <p class="small text-uppercase font-weight-bold text-muted mb-0">Local Statuses</p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card">
        <div class="card-body text-center">
          <p class="h2">{{App\Util\Lexer\PrettyNumber::convert(App\Status::count())}}</p>
          <p class="small text-uppercase font-weight-bold text-muted mb-0">All Statuses</p>
        </div>
      </div>
    </div>
  </div>

  <div class="row mt-4">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body text-center">
          <p class="h2">{{App\Util\Lexer\PrettyNumber::convert(App\Like::count())}}</p>
          <p class="small text-uppercase font-weight-bold text-muted mb-0">Likes</p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card">
        <div class="card-body text-center">
          <p class="h2">{{App\Util\Lexer\PrettyNumber::convert(App\Profile::count())}}</p>
          <p class="small text-uppercase font-weight-bold text-muted mb-0">Profiles</p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card">
        <div class="card-body text-center">
          <p class="h2">{{App\Util\Lexer\PrettyNumber::convert(App\User::count())}}</p>
          <p class="small text-uppercase font-weight-bold text-muted mb-0">Users</p>
        </div>
      </div>
    </div>
  </div>

  <div class="row mt-4">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body text-center">
          <p class="h2">{{App\Util\Lexer\PrettyNumber::convert(App\Status::whereNotNull('url')->distinct('url')->count())}}</p>
          <p class="small text-uppercase font-weight-bold text-muted mb-0">Remote Instances</p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card">
        <div class="card-body text-center">
          <p class="h2">{{App\Util\Lexer\PrettyNumber::convert(App\Media::count())}}</p>
          <p class="small text-uppercase font-weight-bold text-muted mb-0">Photos Uploaded</p>
        </div>
      </div>
    </div>
    @php($filesum = filesize(storage_path('app/public/m/')))
    <div class="col-md-4">
      <div class="card">
        <div class="card-body text-center">
          <p class="human-size" data-bytes="{{App\Media::sum('size')}}">{{App\Media::sum('size')}} bytes</p>
          <p class="small text-uppercase font-weight-bold text-muted mb-0">Storage Used</p>
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