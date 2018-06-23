@extends('admin.partial.template')

@section('section')
  <div class="title">
    <h3 class="font-weight-bold">Users</h3>
  </div>

  <hr>
  <div class="table-responsive">
    <table class="table">
      <thead class="thead-dark">
        <tr>
          <th scope="col">Username</th>
          <th scope="col">Statuses</th>
          <th scope="col">Storage</th>
          <th scope="col">Role</th>
          <th scope="col">Created</th>
        </tr>
      </thead>
      <tbody>
        @foreach($users as $user)
        <tr>
          <th scope="row">
            <a href="{{$user->url()}}">
              {{$user->username}}
            </a>
          </th>
          <td>{{$user->profile->statuses->count()}}</td>
          <td><p class="human-size" data-bytes="{{App\Media::whereUserId($user->id)->sum('size')}}"></p></td>
          <td>{!!$user->is_admin ? '<span class="text-danger">admin</span>' : 'member'!!}</td>
          <td>{{$user->created_at->diffForHumans(null, true, true)}}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="d-flex justify-content-center mt-5 small">
    {{$users->links()}}
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
