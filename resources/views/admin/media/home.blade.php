@extends('admin.partial.template')

@section('section')
  <div class="title">
    <h3 class="font-weight-bold">Media</h3>
  </div>

  <hr>

  <div class="profile-timeline mt-5 row">
    @foreach($media as $status)
    <div class="col-12 col-md-4 mb-4">
      <a class="card" href="{{$status->url()}}">
        <img class="card-img-top" src="{{$status->thumb()}}" width="150px" height="150px"
          @if($status->description)
          alt="{{$status->description}}"
          @endif
        >
      </a>
    </div>
    @endforeach
  </div>

    <hr>
  <div class="d-flex justify-content-center">
    {{$media->links()}}
  </div>
@endsection
