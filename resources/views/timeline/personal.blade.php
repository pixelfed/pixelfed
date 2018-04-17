@extends('layouts.app')

@section('content')

<div class="container">
  <div class="col-12 col-md-6 offset-md-3">
    @if ($errors->any())
      <div class="alert alert-danger">
          <ul>
              @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
              @endforeach
          </ul>
      </div>
    @endif
    
    <div class="card">
      <div class="card-header font-weight-bold">New Status Post</div>
      <div class="card-body">
        <form method="post" action="/timeline" enctype="multipart/form-data">
          @csrf
          <div class="form-group">
            <label class="font-weight-bold text-muted small">Upload Image</label>
            <input type="file" class="form-control-file" name="photo">
          </div>
          <div class="form-group">
            <label class="font-weight-bold text-muted small">Caption</label>
            <input type="text" class="form-control" name="caption" placeholder="Add a caption here">
          </div>
          <button type="submit" class="btn btn-primary">Submit</button>
        </form>
      </div>  
    </div>

    <div class="timeline-feed my-5" data-timeline="personal">
  @foreach($timeline as $item)
    @include('status.template')

  @endforeach
    </div>

  </div>
</div>


@endsection