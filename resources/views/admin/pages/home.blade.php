@extends('admin.partial.template')

@include('admin.settings.sidebar')

@section('section')
  <div class="title">
    <h3 class="font-weight-bold">Pages</h3>
    <p class="lead">Set custom page content</p>
  </div>
  <hr>
  <p class="alert alert-warning">
    <strong>Feature Unavailable:</strong> This feature will be released in v0.9.0.
  </p>
  {{--< div class="table-responsive">
    <table class="table">
      <thead class="bg-light">
        <tr class="text-center">
          <th scope="col" class="border-0" width="5%">
            <span>ID</span> 
          </th>
          <th scope="col" class="border-0" width="50%">
            <span>Slug</span>
          </th>
          <th scope="col" class="border-0" width="15%">
            <span>Active</span>
          </th>
          <th scope="col" class="border-0" width="30%">
            <span>Updated</span>
          </th>
        </tr>
      </thead>
      <tbody>
        @foreach($pages as $page)
        <tr class="font-weight-bold text-center page-row">
          <th scope="row">
            <a href="{{$page->editUrl()}}">{{$page->id}}</a>
          </th>
          <td>{{$page->slug}}</td>
          <td>{{$page->active ? 'active':'inactive'}}</td>
          <td>{{$page->updated_at->diffForHumans(null, true, true, true)}}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="d-flex justify-content-center mt-5 small">
    {{$pages->links()}}
  </div> --}}
@endsection