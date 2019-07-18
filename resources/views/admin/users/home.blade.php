@extends('admin.partial.template-full')

@section('header')
<div class="bg-primary">
  <div class="container">
    <div class="my-5"></div>
  </div>
</div>
@endsection

@section('section')
  <div class="title">
    <h3 class="font-weight-bold">Users</h3>
  </div>
  <hr>
  <div class="table-responsive">
    <table class="table">
      <thead class="bg-light">
        <tr class="text-center">
          <th scope="col" class="border-0" width="10%">
            <span>ID</span> 
          </th>
          <th scope="col" class="border-0" width="30%">
            <span>Username</span>
          </th>
          <th scope="col" class="border-0" width="15%">
            <span>Statuses</span>
          </th>
          <th scope="col" class="border-0" width="15%">
            <span>Storage</span>
          </th>
          <th scope="col" class="border-0" width="30%">
            <span>Actions</span>
          </th>
        </tr>
      </thead>
      <tbody>
        @foreach($users as $user)
        <tr class="font-weight-bold text-center user-row">
          <th scope="row">
            <span class="{{$user->status == 'deleted' ? 'text-danger':''}}">{{$user->id}}</span>
          </th>
          <td class="text-left">
            <img src="{{$user->profile ? $user->profile->avatarUrl() : '/storage/avatars/default.png?v=1'}}" width="28px" class="rounded-circle mr-2" style="border:1px solid #ccc">
            <span title="{{$user->username}}" data-toggle="tooltip" data-placement="bottom">
              <span class="{{$user->status == 'deleted' ? 'text-danger':''}}">{{$user->username}}</span>
              @if($user->is_admin)
               <i class="text-danger fas fa-certificate" title="Admin"></i>
              @endif
            </span>
          </td>
         <td>
            <span class="{{$user->status == 'deleted' ? 'text-danger':''}}">{{$user->profile ? $user->profile->statusCount() : 0}}</span>
          </td>
          <td>
            <span class="{{$user->status == 'deleted' ? 'text-danger':''}}"><p class="human-size mb-0" data-bytes="{{App\Media::whereUserId($user->id)->sum('size')}}"></p></span>
          </td>
          <td>
            <span class="action-row font-weight-lighter">
              <a href="{{$user->url()}}" class="pr-2 text-muted small font-weight-bold" title="View Profile" data-toggle="tooltip" data-placement="bottom">
                View
              </a>

              <a href="/i/admin/users/edit/{{$user->id}}" class="pr-2 text-muted small font-weight-bold" title="Edit Profile" data-toggle="tooltip" data-placement="bottom">
                Edit
              </a>

              <a href="#" class="text-muted action-btn small font-weight-bold" title="Delete Profile" data-toggle="tooltip" data-placement="bottom" data-id="{{$user->id}}" data-action="delete">
                Delete
              </a>
            </span>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="d-flex justify-content-center mt-5 small">
    {{$users->links()}}
  </div>
@endsection

@push('styles')
<style type="text/css">
.jqstooltip {
  -webkit-box-sizing: content-box;
  -moz-box-sizing: content-box;
  box-sizing: content-box;
  border: 0 !important;
  border-radius: 2px;
  max-width: 20px;
}

.user-row .action-row {
  display: none;
}

.user-row:hover {
  background-color: #eff8ff;
}
.user-row:hover .action-row {
  display: block;
}
.user-row:hover .last-active {
  display: none;
}
</style>
@endpush
@push('scripts')
  <script type="text/javascript">
    $(document).ready(function() {

      $('.human-size').each(function(d,a) {
        let el = $(a);
        let size = el.data('bytes');
        el.text(filesize(size, {round: 0}));
      });

      $(document).on('click', '.action-btn', function(e) {
        e.preventDefault();

        let el = $(this);
        let id = el.data('id');
        let action = el.data('action');

        switch(action) {
          case 'view':
          window.location.href = el.data('url');
          break;
          case 'edit':
          let redirect = '/i/admin/users/edit/' + id;
          window.location.href = redirect;
          break;
          case 'delete':
          swal('Error', 'Sorry this action is not yet available', 'error');
          break;
        }
      });

    });
  </script>
@endpush
