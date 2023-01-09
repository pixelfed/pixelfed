@extends('admin.partial.template-full')

@section('section')
</div>
<div class="header bg-primary pb-3 mt-n4">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <p class="display-1 text-white mb-0">Newsroom</p>
                    <p class="lead text-white my-0">Manage News and Platform Tips</p>
                </div>

                <div class="col-lg-6 col-5">
                    <div class="text-right">
                        <a class="btn btn-danger px-4" style="font-size:13px;" href="{{route('admin.newsroom.create')}}">New Announcement</a>
                        <a class="btn btn-dark px-4 mr-3" style="font-size:13px;" href="/site/newsroom">View Newsroom <i class="fas fa-chevron-right fa-sm text-lighter ml-1"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid mt-4">
    <div class="row mb-3 justify-content-between">
        <div class="col-12">
            @if($newsroom->count() > 0)
            <div class="table-responsive">
                <table class="table table-dark">
                    <thead class="thead-dark">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Title</th>
                            <th scope="col">Slug</th>
                            <th scope="col">Status</th>
                            <th scope="col">Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($newsroom as $news)
                        <tr>
                            <td class="font-weight-bold text-monospace text-muted">
                                <a href="{{$news->editUrl()}}">
                                    {{ $news->id }}
                                </a>
                            </td>
                            <td class="font-weight-bold">
                                <div>
                                  <p class="mb-0 font-weight-bold">{{str_limit($news->title, 50)}}</p>
                                  {{-- <p class="mb-0 small">{{str_limit($news->summary, 80)}}</p> --}}
                                </div>
                            </td>
                            <td class="text-muted">
                                @if($news->published_at)
                                    <a href="{{$news->permalink()}}" class="font-weight-bold" target="_blank">{{$news->slug}}</a>
                                @else
                                    {{ $news->slug }}
                                @endif
                            </td>
                            <td class="font-weight-bold">
                                @if($news->published_at != null)
                                  <span class="badge badge-success font-weight-bold mr-3">PUBLISHED</span>
                                @else
                                  <span class="badge badge-dark font-weight-bold mr-3">DRAFT</span>
                                @endif
                            </td>

                            <td class="font-weight-bold">
                                {{ $news->updated_at->diffForHumans() }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-4">
                {!!$newsroom->links()!!}
            </div>
            @else
            <div class="card-body text-center">
                <p class="lead mb-0 p-5">No Announcements Found!</p>
            </div>
            @endif
      </div>
    </div>
</div>

@endsection
