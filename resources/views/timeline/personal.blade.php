@extends('layouts.app')

@section('content')

<div class="container">
  <div class="col-12 col-md-6 offset-md-3">
    
    <div class="card">
      <div class="card-header font-weight-bold">New Status Post</div>
      <div class="card-body">
        <form>
          <div class="form-group">
            <label class="font-weight-bold text-muted small">Upload Image</label>
            <input type="file" class="form-control-file">
          </div>
          <div class="form-group">
            <label class="font-weight-bold text-muted small">Caption</label>
            <input type="text" class="form-control" placeholder="Add a caption here">
          </div>
          <button type="submit" class="btn btn-primary">Submit</button>
        </form>
      </div>  
    </div>

  </div>
</div>


@endsection