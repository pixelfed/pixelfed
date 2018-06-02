@extends('layouts.app')

@push('scripts')
<script type="text/javascript" src="{{mix('js/timeline.js')}}"></script>
@endpush 

@section('content')

<div class="container">
  <div class="col-12 col-md-8 offset-md-2 pt-4">
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
      <div class="card-header font-weight-bold">New Post</div>
      <div class="card-body" id="statusForm">
        <form method="post" action="/timeline" enctype="multipart/form-data">
          @csrf
          <div class="form-group">
            <label class="font-weight-bold text-muted small">Upload Image</label>
            <input type="file" class="form-control-file" name="photo" accept="image/*">
          </div>
          <div class="form-group">
            <label class="font-weight-bold text-muted small">Caption</label>
            <input type="text" class="form-control" name="caption" placeholder="Add a caption here. Up to 150 characters." maxlength=150>
          </div>
          <button type="submit" class="btn btn-outline-primary btn-block">Post</button>
        </form>
      </div>  
    </div>

    <div class="timeline-feed my-5" data-timeline="personal">
    @foreach($timeline as $item)

      @include('status.template')

    @endforeach
    @if($timeline->count() == 0)
    <div class="card">
      <div class="card-body py-5">
        <div class="d-flex justify-content-center align-items-center">
          <p class="lead font-weight-bold mb-0">{{ __('timeline.emptyPersonalTimeline') }}</p>
        </div>
      </div>
    </div>
    @endif
    </div>
    <div class="d-flex justify-content-center">
      {{$timeline->links()}}
    </div>

  </div>
</div>


@endsection
