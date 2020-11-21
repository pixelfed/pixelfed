@extends('admin.partial.template-full')

@section('section')
  <div class="title">
    <h3 class="font-weight-bold">Statuses</h3>
  </div>

  <hr>

  <table class="table">
    <thead class="bg-light">
      <tr>
        <th scope="col" class="border-0 text-dark">#</th>
        <th scope="col" class="border-0 text-dark">Username</th>
        <th scope="col" class="border-0 text-dark">Likes</th>
        <th scope="col" class="border-0 text-dark">Storage</th>
        <th scope="col" class="border-0 text-dark">Created</th>
      </tr>
    </thead>
    <tbody>
      @foreach($statuses as $status)
      <tr>
        <th scope="row">
          <a href="{{$status->url()}}">
            {{$status->id}}
          </a>
        </th>
        <td class="font-weight-bold">{{$status->profile->username}}</td>
        <td class="font-weight-bold">{{$status->likes()->count()}}</td>
        @if(!$status->media_path)
        <td class="font-weight-bold">0</td>
        @else
        <td><div class="human-size" data-bytes="{{$status->firstMedia()->size}}">{{$status->firstMedia()->size}}</div></td>
        @endif
        <td class="font-weight-bold">{{$status->created_at->diffForHumans(null, true, true, true)}}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  <div class="d-flex justify-content-center mt-5 small">
    {{$statuses->links()}}
  </div>
@endsection

@push('scripts')
  <script type="text/javascript">
    $(document).ready(function() {
      $('.human-size').each(function(d,a) {
        let el = $(a);
        let size = el.data('bytes');
        el.text(filesize(size, {round: 0}));
      });
    });
  </script>
@endpush