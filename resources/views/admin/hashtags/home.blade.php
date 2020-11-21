@extends('admin.partial.template-full')

@section('section')
<div class="title">
	<h3 class="font-weight-bold d-inline-block">Hashtags</h3>
</div>
<hr>
  <table class="table table-responsive">
    <thead class="bg-light">
      <tr>
        <th scope="col" width="10%">#</th>
        <th scope="col" width="30%">Hashtag</th>
        <th scope="col" width="15%">Status Count</th>
        <th scope="col" width="10%">NSFW</th>
        <th scope="col" width="10%">Banned</th>
        <th scope="col" width="15%">Created</th>
      </tr>
    </thead>
    <tbody>
      @foreach($hashtags as $tag)
      <tr>
        <td>
          <a href="/i/admin/apps/show/{{$tag->id}}" class="btn btn-sm btn-outline-primary">
           	{{$tag->id}}
          </a>
        </td>
        <td class="font-weight-bold">{{$tag->name}}</td>
        <td class="font-weight-bold text-center">
        	<a href="{{$tag->url()}}">
        		{{$tag->posts()->count()}}
        	</a>
        </td>
        <td class="font-weight-bold">{{$tag->is_nsfw ? 'true' : 'false'}}</td>
        <td class="font-weight-bold">{{$tag->is_banned ? 'true' : 'false'}}</td>
        <td class="font-weight-bold">{{$tag->created_at->diffForHumans()}}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  <div class="d-flex justify-content-center mt-5 small">
    {{$hashtags->links()}}
  </div>
@endsection
