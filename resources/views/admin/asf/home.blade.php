@extends('admin.partial.template-full')

@section('section')
</div><div class="header bg-primary pb-3 mt-n4">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <p class="display-1 text-white d-inline-block mb-0">Admin Shadow Filters</p>
                    <p class="text-white mb-0">Manage shadow filters across Accounts, Hashtags, Feeds and Stories</p>
                </div>
            </div>
        </div>
    </div>
</div>
    <div class="m-n2 m-lg-4">
        <div class="container-fluid mt-4">
            <div class="row mb-3 justify-content-between">
                <div class="col-12 col-md-8">
                    <ul class="nav nav-pills">
                        <li class="nav-item">
                            <a class="nav-link {{request()->has('filter') ? '':'active'}}" href="/i/admin/asf/home">Active Filters</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{request()->has('filter') && request()->filter == 'all' ? 'active':''}}" href="/i/admin/asf/home?filter=all">All</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{request()->has('filter') && request()->filter == 'inactive' ? 'active':''}}" href="/i/admin/asf/home?filter=inactive">Inactive</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{request()->has('new') ? 'active':''}}" href="/i/admin/asf/create">New</a>
                        </li>
                    </ul>
                </div>

                <div class="col-12 col-md-4">
                    <form method="get">
                        <input class="form-control" placeholder="Search by username" name="q" value="{{request()->has('q') ? request()->query('q') : ''}}" />
                    </form>
                </div>
            </div>

            <div class="table-responsive rounded">
                <table class="table table-dark">
                    <thead class="thead-dark">
                        <tr>
                            <th scope="col" class="cursor-pointer">ID</th>
                            <th scope="col" class="cursor-pointer">Username</th>
                            <th scope="col" class="cursor-pointer">Hide Feeds</th>
                            <th scope="col" class="cursor-pointer">Active</th>
                            <th scope="col" class="cursor-pointer">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($filters as $filter)
                        <tr>
                            <td><a href="/i/admin/asf/edit/{{$filter->id}}">{{ $filter->id }}</a></td>
                            <td>
                                <div class="d-flex align-items-center" style="gap: 1rem;">

                                <img src="{{ $filter->account()['avatar'] }}" class="rounded-circle" width="30" height="30" onerror="this.src='/storage/avatars/default.jpg';this.onerror=null;" />
                                <p class="font-weight-bold mb-0">
                                    &commat;{{ $filter->account()['acct'] }}
                                </p>
                                </div>
                            </td>
                            <td>{{ $filter->hide_from_public_feeds ? '✅' : ''}}</td>
                            <td>{{ $filter->active ? '✅' : ''}}</td>
                            <td>{{ $filter->created_at->diffForHumans() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="d-flex mt-3">
                    {{ $filters->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
