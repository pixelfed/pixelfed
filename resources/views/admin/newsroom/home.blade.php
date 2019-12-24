@extends('admin.partial.template-full')

@section('section')
<div class="d-flex justify-content-between align-items-center">
  
  <div class="title">
    <p class="h1 font-weight-bold">Newsroom</p>
    <p class="lead mb-0">Manage News and Platform Tips</p>
  </div>

  <div>
    <a class="btn btn-outline-success px-4" style="font-size:13px;" href="{{route('admin.newsroom.create')}}">New Announcement</a>
    <a class="btn btn-outline-secondary px-2 mr-3" style="font-size:13px;" href="/site/newsroom">View Newsroom <i class="fas fa-chevron-right fa-sm text-lighter ml-1"></i></a>
  </div>
</div>

<div class="my-5 row">
  <div class="col-md-8 offset-md-2">
    <div class="card">
      <div class="card-header bg-light lead font-weight-bold">
        Announcements
      </div>
      @if($newsroom->count() > 0)
      <ul class="list-group list-group-flush">
        @foreach($newsroom as $news)
          <li class="list-group-item d-flex align-items-center justify-content-between">
            <div>
              <p class="mb-0 font-weight-bold">{{str_limit($news->title,30)}}</p>
              <p class="mb-0 small">{{str_limit($news->summary, 40)}}</p>
            </div>
            <div>
              @if($news->published_at != null)
              <span class="btn btn-success btn-sm px-2 py-0 font-weight-bold mr-3">PUBLISHED</span>
              @else
              <span class="btn btn-outline-secondary btn-sm px-2 py-0 font-weight-bold mr-3">DRAFT</span>
              @endif
              <a class="btn btn-outline-lighter btn-sm mr-2" title="Edit Post" data-toggle="tooltip" data-placement="bottom" href="{{$news->editUrl()}}">
                <i class="fas fa-edit"></i>
              </a>
              @if($news->published_at)
              <a class="btn btn-outline-lighter btn-sm" title="View Post" data-toggle="tooltip" data-placement="bottom" href="{{$news->permalink()}}">
                <i class="fas fa-eye"></i>
              </a>
              @endif
            </div>
          </li>
        @endforeach
      </ul>
      @else
      <div class="card-body text-center">
        <p class="lead mb-0 p-5">No Announcements Found!</p>
      </div>
      @endif
    </div>
    <div class="d-flex justify-content-center mt-4">
      {!!$newsroom->links()!!}
    </div>
  </div>

</div>

@endsection
