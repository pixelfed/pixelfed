@extends('layouts.app')

@section('content')
<div class="container">
  <div class="col-12">
    <div class="card mt-5">
      <div class="card-body p-0">
        <div class="row">
          <div class="col-12 col-md-8 offset-md-2 p-5">
            <div class="title text-center pb-3">
              <h1>Remote Follow</h1>
              <div class="card">
                <div class="card-body">
                  <p class="mb-0 font-weight-bold">This feature is not yet ready for production. Please try again later.</p>
                </div>
              </div>
            </div>
            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif
            <form method="post">
              @csrf
              <div class="card rounded-0 card-disabled">
                <div class="card-body">
                  <div class="form-group row mb-2">
                    <label class="col-sm-3 col-form-labe font-weight-bold">Profile URL</label>
                    <div class="col-sm-8">
                      <input type="text" class="form-control" name="url" value="" disabled>
                      <p class="help-text small text-muted">ex: me@example.com or http://example.net/nickname</p>
                    </div>
                  </div>
                </div>
                <div class="card-footer px-5 text-center">
                    <button type="submit" class="btn btn-primary py-0 font-weight-bold" disabled>Remote Follow</button>
                </div>
              </div>

            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection