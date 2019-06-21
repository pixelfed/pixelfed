@extends('admin.partial.template')

@include('admin.settings.sidebar')

@section('section')
  <div class="title">
    <h3 class="font-weight-bold">Configuration Settings</h3>
    @if($editor == false)
    <hr>
    <div class="card bg-light shadow-none rounded-0">
      <div class="card-body text-center py-5">
        <p class="lead text-muted font-weight-bold">Configuration Editor is disabled</p>
        <p class="mb-0">To enable it, add <code>ADMIN_ENV_EDITOR=true</code> to <code>.env</code><br>then run <code>php artisan config:cache</code></p>
      </div>
    </div>
    @else
    <p class="lead">Edit configuration settings</p>
    <p class="alert alert-warning">
      <strong>Warning:</strong> If you have opcache enabled, you may need to restart php for the changes to take effect.
    </p>
  </div>
  <hr>
  <div>
    <div id="editor">{{$config}}</div>
    <hr>
    <div class="d-flex justify-content-between px-3">
      @if($backup)
      <button class="btn btn-outline-secondary font-weight-bold py-1 btn-restore">Restore backup .env</button>
      @else
      <div></div>
      @endif
      <button class="btn btn-primary font-weight-bold py-1 btn-save">Save</button>
    </div>
  </div>
  @endif
@endsection
@if($editor == true)
@push('scripts')
<script src="{{mix('js/ace.js')}}"></script>
<script>
    let editor = ace.edit("editor");
    editor.session.setUseWrapMode(true);
    editor.setTheme("ace/theme/monokai");
    editor.session.setMode("ace/mode/dot");

    $('.btn-restore').on('click', function(e) {
      e.preventDefault();
      let confirm = window.confirm('Are you sure you want to restore your backup .env?');
      if(!confirm) {
        swal('Cancelled', 'You have cancelled the .env backup restore.', 'warning');
        return;
      }
      axios.post('/i/admin/settings/config/restore', {
      }).then(res => {
        swal('Success', 'Configuration successfully restored!', 'success');
        setTimeout(function() {
          window.location.href = window.location.href;
        }, 3000);
      });
    })

    $('.btn-save').on('click', function(e) {
      e.preventDefault();
      let confirm = window.confirm('Are you sure you want to overwrite your current .env?');
      if(!confirm) {
        swal('Cancelled', 'You have cancelled the .env update.', 'warning');
        return;
      }
      axios.post('/i/admin/settings/config', {
        res: editor.getValue()
      }).then(res => {
        swal('Success', 'Configuration successfully updated!', 'success');
        setTimeout(function() {
          window.location.href = window.location.href;
        }, 3000);
      });
    })
</script>
@endpush

@push('styles')
<style type="text/css" media="screen">
    #editor { 
        display: block;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        width: 100%;
        min-height: 400px;
    }
</style>
@endpush
@endif