@extends('admin.partial.template')

@section('section')
  <div class="title">
    <h3 class="font-weight-bold">Site Backups</h3>
  </div>
  <hr>
  <div class="row">
    <div class="col-md-7">
      <div class="card">
        <div class="card-header font-weight-bold">Settings</div>
        <div class="card-body">
          <form>
            <div class="form-group pt-3">
              <label class="font-weight-bold text-muted small">Auto Backup Enabled</label>
              <div class="switch switch-sm">
                <input type="checkbox" class="switch" id="cw-switch" name="cw">
                <label for="cw-switch" class="small font-weight-bold">(Default off)</label>
              </div>
              <small class="form-text text-muted">
                Enable automated backups with your own strategy.
              </small>
            </div>
            <div class="form-group pt-3">
              <label class="font-weight-bold text-muted small">Frequency</label>
              <select class="form-control">
                <option>Hourly (1h)</option>
                <option selected="">Nightly (24h)</option>
                <option>Weekly (7d)</option>
                <option>Monthly (1m)</option>
              </select>
              <small class="form-text text-muted">
                Select the backup frequency.
              </small>
            </div>
            <div class="form-group pt-3">
              <label class="font-weight-bold text-muted small">Storage Filesystem</label>
              <select class="form-control">
                <option>Local</option>
                <option disabled="">S3 (Not configured)</option>
              </select>
              <small class="form-text text-muted">
                You can use local, S3, or any S3 compatible object storage API to store backups.
              </small>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="col-md-5">
      <div class="card">
        <div class="card-header font-weight-bold">Current Backups</div>
        <div class="list-group list-group-flush">
          @foreach($files as $file)
          @if($file->isFile())
          <li class="list-group-item pb-0">
            <p class="font-weight-bold mb-0 text-truncate">{{$file->getFilename()}}</p>
            <p class="mb-0 small text-muted font-weight-bold">
              <span>
                Size: {{App\Util\Lexer\PrettyNumber::convert($file->getSize())}}
              </span>
              <span class="float-right">
                Created: {{\Carbon\Carbon::createFromTimestamp($file->getMTime())->diffForHumans()}}</p>
              </span>
            </p>
          </li>
          @endif
          @endforeach
        </div>
      </div>
      
    </div>
  </div>
@endsection
