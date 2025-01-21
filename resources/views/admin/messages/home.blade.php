@extends('admin.partial.template-full')

@section('section')
</div>
<div class="header bg-primary pb-3 mt-n4">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <p class="display-1 text-white d-inline-block mb-0">Messages</p>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container mt-3">

    <div class="row justify-content-center">
        @if (session('status'))
        <div class="col-12" id="flash">
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        </div>
        @endif
        @if (session('error'))
        <div class="col-12" id="flash">
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        </div>
        @endif
        <div class="col-12">
            <ul class="nav nav-pills my-3">
                <li class="nav-item">
                    <a class="nav-link {{$sort=='all'?'active':''}}" href="?sort=all">All</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{$sort=='open'?'active':''}}" href="?sort=open">Open</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{$sort=='closed'?'active':''}}" href="?sort=closed">Closed</a>
                </li>
            </ul>
        </div>
        <div class="col-12">
            <div class="table-responsive">
              <table class="table">
                <thead class="bg-light">
                  <tr>
                    <th scope="col">#</th>
                    <th scope="col">User</th>
                    <th scope="col">Message</th>
                    <th scope="col">Created</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($messages as $msg)
                  <tr>
                    <td>
                      <a href="/i/admin/messages/show/{{$msg->id}}" class="btn btn-sm btn-outline-primary">
                       	{{$msg->id}}
                      </a>
                    </td>
                    <td class="font-weight-bold"><a href="{{$msg->user->url()}}">{{$msg->user->username}}</a></td>
                    <td class="font-weight-bold">{{str_limit($msg->message, 40)}}</td>
                    <td class="font-weight-bold">{{$msg->created_at->diffForHumans()}}</td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <hr />
            {{$messages->links()}}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
    function checkAndRemoveElementOnLoad(selector, delay, action = 'hide') {
        window.addEventListener('load', () => {
            setTimeout(() => {
                const element = document.querySelector(selector);
                if (element) {
                    if (action === 'hide') {
                        element.style.display = 'none';
                    } else if (action === 'remove') {
                        element.remove();
                    }
                }
            }, delay * 1000);
        });
    }

    checkAndRemoveElementOnLoad('#flash', 5, 'remove');
</script>
@endpush
