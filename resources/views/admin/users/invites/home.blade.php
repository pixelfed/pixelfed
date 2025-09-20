@extends('admin.partial.template-full')

@section('section')
    <div class="container-fluid">
        <div class="row align-items-center mb-4">
            <div class="col-md-6">
                <div class="display-1 font-weight-bold text-dark mb-0">
                    Invites
                </div>
                <p class="text-muted mb-0">Manage admin-created user invitations</p>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-md-end mb-4">
                    <a href="{{ route('admin.users.invites.create') }}" class="btn btn-primary" title="Invites">
                        <span class="font-weight-bold">New Invite</span>
                    </a>
                </div>
            </div>
        </div>

        @if (session('status'))
            <div class="row justify-content-center">
                <div class="col-12" id="flash">
                    <div class="alert alert-success">
                        {!! session('status') !!}
                    </div>
                </div>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table">
                <thead class="bg-light">
                <tr>
                    <th scope="col" class="border-0">
                        <span>Code</span>
                    </th>
                    <th scope="col" class="border-0">
                        <span>Name</span>
                    </th>
                    <th scope="col" class="border-0">
                        <span>Description</span>
                    </th>
                    <th scope="col" class="border-0">
                        <span>Remaining Uses</span>
                    </th>
                    <th scope="col" class="border-0">
                        <span>Expires</span>
                    </th>
                    <th scope="col" class="border-0">
                        <span>Actions</span>
                    </th>
                </tr>
                </thead>
                <tbody>
                @foreach($invites as $invite)
                    <tr class="font-weight-bold">
                        <td class="text-truncate" data-toggle="tooltip" data-placement="bottom" title="{{ $invite->description }}" style="max-width: 100px;">
                            <a href="{{ $invite->url() }}">{{ $invite->invite_code }}</a>
                        </td>
                        <td>
                            {{$invite->name}}
                        </td>
                        <td class="text-truncate" data-toggle="tooltip" data-placement="bottom" title="{{ $invite->description }}" style="max-width: 200px;">
                            {{ $invite->description }}
                        </td>
                        <td>
                            {{ $invite->max_uses ? ($invite->max_uses - $invite->uses) : '∞' }}
                        </td>
                        <td data-toggle="tooltip" data-placement="bottom" title="{{ $invite->expires_at?->toDateTimeString() ?? '' }}" style="max-width: 80px;">
                            {{ $invite->hasExpired() ? 'expired' : ($invite->expires_at?->diffForHumans() ?? 'never') }}
                        </td>
                        <td>
                            <button class="btn btn-outline-secondary btn-sm py-0 mr-3"
                                    onclick="expireInvite('{{ $invite->name ?? $invite->invite_code }}', '{{ route('admin.users.invites.expire', $invite) }}')"
                                    type="button">
                                Expire
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="d-flex justify-content-center mt-5 small">
                {{ $invites->links() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function expireInvite (inviteName, deletionRoute) {
            event.preventDefault()

            swal({
                title: 'Expire Invite',
                text: `Are you sure you want to expire the invite "${inviteName}"? This action cannot be undone.`,
                icon: 'warning',
                dangerMode: true,
                buttons: {
                    cancel: {
                        text: 'Cancel',
                        value: false,
                        visible: true,
                    },
                    expire: {
                        text: 'Expire',
                        value: 'expire',
                        className: 'btn-danger'
                    }
                }
            })
                .then((willExpire) => {
                    if (willExpire === 'expire') {
                        swal({
                            title: 'Deleting...',
                            text: 'Please wait while we expire the invite.',
                            icon: 'info',
                            buttons: false,
                            closeOnClickOutside: false,
                            closeOnEsc: false
                        })

                        axios.post(deletionRoute)
                            .then(res => {
                                swal('Success!', 'Invite has been expired successfully.', 'success')
                                    .then(() => {
                                        window.location.reload()
                                    })
                            })
                            .catch(err => {
                                console.error('Expire error:', err)
                                swal('Error!', 'Failed to expire invite. Please try again.', 'error')
                            })
                    }
                })
        }
    </script>
@endpush
