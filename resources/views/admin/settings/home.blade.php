@extends('admin.partial.template')

@include('admin.settings.sidebar')

@section('section')
  <div class="title">
    <h3 class="font-weight-bold">Settings</h3>
  </div>
  <hr>
  <form method="post">
    @csrf
    <div class="form-group row">
      <label for="app_name" class="col-sm-3 col-form-label font-weight-bold text-right">App Name</label>
      <div class="col-sm-9">
        <input type="text" class="form-control" id="app_name" name="APP_NAME" placeholder="Application Name ex: pixelfed" value="{{config('app.name')}}" autocomplete="off">
        <p class="text-muted small help-text font-weight-bold mb-0">Site name, default: pixelfed</p>
      </div>
    </div>
    <div class="form-group row">
      <label for="app_url" class="col-sm-3 col-form-label font-weight-bold text-right">App URL</label>
      <div class="col-sm-9">
        <input type="text" class="form-control" id="app_url" name="APP_URL" placeholder="Application URL" value="{{config('app.url')}}">
        <p class="text-muted small help-text font-weight-bold mb-0">App URL, used for building URLs ex: https://example.org</p>
      </div>
    </div>

    <div class="form-group row">
      <label for="app_url" class="col-sm-3 col-form-label font-weight-bold text-right">App Domain</label>
      <div class="col-sm-9">
        <input type="text" class="form-control" id="app_url" name="app_domain" placeholder="example.org" value="{{config('pixelfed.domain.app')}}">
        <p class="text-muted small help-text font-weight-bold mb-0">Used for routing ex: example.org</p>
      </div>
    </div>


    <div class="form-group row">
      <label for="app_url" class="col-sm-3 col-form-label font-weight-bold text-right">Admin Domain</label>
      <div class="col-sm-9">
        <input type="text" class="form-control" id="admin_domain" name="admin_domain" placeholder="admin.example.org" value="{{config('pixelfed.domain.admin')}}">
        <p class="text-muted small help-text font-weight-bold mb-0">Used for routing the admin dashboard ex: admin.example.org</p>
      </div>
    </div>

    {{-- <div class="alert alert-info border-0">
      <div class="media d-flex align-items-center">
      <div class="mr-3">
        <i class="fas fa-info-circle fa-2x"></i>
      </div>
      <div class="media-body">
        <p class="mb-0 lead">Tip:</p>
        <p class="mb-0">You can edit the .env file in the <a href="#" class="font-weight-bold">Configuration</a> settings.</p>
      </div>
      </div>
    </div>

    <hr>
    <div class="form-group row mb-0">
      <div class="col-12 text-right">
        <button type="submit" class="btn btn-primary font-weight-bold">Submit</button>
      </div>
    </div> --}}
  </form>
@endsection