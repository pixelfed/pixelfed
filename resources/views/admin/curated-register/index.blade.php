@extends('admin.partial.template-full')

@section('section')
</div><div class="header bg-primary pb-3 mt-n4">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-8 col-12">
                    <p class="display-1 text-white d-inline-block mb-0">Curated Onboarding</p>
                    <p class="text-white mb-0">The ideal solution for communities seeking a balance between open registration and invite-only membership</p>
                </div>
            </div>
        </div>
    </div>
</div>

@if((bool) config_cache('instance.curated_registration.enabled'))
<div class="m-n2 m-lg-4">
    <div class="container-fluid mt-4">
        @include('admin.curated-register.partials.nav')

        @if($records && $records->count())
        <div class="table-responsive rounded">
            <table class="table table-dark">
                <thead class="thead-dark">
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Username</th>
                        @if(in_array($filter, ['all', 'open', 'awaiting', 'responses']))
                        <th scope="col">Status</th>
                        @endif
                        <th scope="col">Reason for Joining</th>
                        <th scope="col">Email</th>
                        <th scope="col">Created</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $record)
                    <tr>
                        <td class="align-middle">
                            <a href="/i/admin/curated-onboarding/show/{{$record->id}}" class="font-weight-bold">
                                #{{ $record->id }}
                            </a>
                        </td>
                        <td>
                            <p class="font-weight-bold mb-0">
                                &commat;{{ $record->username }}
                            </p>
                        </td>
                        @if(in_array($filter, ['all', 'open', 'awaiting', 'responses']))
                        <td class="align-middle">
                            {!! $record->adminStatusLabel() !!}
                        </td>
                        @endif
                        <td class="align-middle">
                            {{ str_limit($record->reason_to_join, 100) }}
                        </td>
                        <td class="align-middle">
                            <p class="mb-0">
                                {{ str_limit(\Illuminate\Support\Str::mask($record->email, '*', 5, 10), 10) }}
                            </p>
                        </td>
                        <td class="align-middle">{{ $record->created_at->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="d-flex mt-3">
                {{ $records->links() }}
            </div>
        </div>
        @else
        <div class="card">
            <div class="card-body">
                <p class="text-center"><i class="far fa-check-circle fa-6x text-success"></i></p>
                <p class="lead text-center">No {{ request()->filled('filter') ? request()->filter : 'open' }} applications found!</p>
            </div>
        </div>
        @endif
    </div>
</div>
@else
@include('admin.curated-register.partials.not-enabled')
@endif
@endsection
