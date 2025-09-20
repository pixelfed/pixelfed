@extends('admin.partial.template-full')

@section('section')
<div class="container-fluid">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <div class="display-1 font-weight-bold text-dark mb-0">
                Users
            </div>
            <p class="text-muted mb-0">Manage and moderate user accounts</p>
        </div>
        <div class="col-md-6">
                <div class="d-flex justify-content-md-end mb-4">
                    <a href="{{ route('admin.users.invites.index') }}" class="btn btn-secondary" title="Invites">
                        <span class="font-weight-bold">Invites</span>
                    </a>
                </div>
            <form method="get" class="d-flex justify-content-md-end">
                <input type="hidden" name="a" value="search">
                @if(request()->has('col'))<input type="hidden" name="col" value="{{request()->query('col')}}">@endif
                @if(request()->has('dir'))<input type="hidden" name="dir" value="{{request()->query('dir')}}">@endif
                @if(request()->has('limit'))<input type="hidden" name="limit" value="{{request()->query('limit')}}">@endif
                @if(request()->has('trashed'))<input type="hidden" name="trashed" value="1">@endif
                <div class="input-group" style="max-width: 350px;">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-lighter border-right-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                    </div>
                    <input class="form-control border-left-0 pl-2" name="q" placeholder="Search by username..." value="{{request()->input('q')}}">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary">
                            Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex flex-wrap align-items-center" style="gap:2rem;">
                        <div class="mr-4 mb-2 mb-md-0 d-flex align-items-center">
                                <label for="limitSelect" class="text-muted small mb-0 mr-2">Show:</label>
                                <select id="limitSelect" class="custom-select custom-select-sm" style="width: auto;" onchange="changeLimit(this.value)">
                                    <option value="10" {{$limit == 10 ? 'selected' : ''}}>10</option>
                                    <option value="25" {{$limit == 25 ? 'selected' : ''}}>25</option>
                                    <option value="50" {{$limit == 50 ? 'selected' : ''}}>50</option>
                                    <option value="100" {{$limit == 100 ? 'selected' : ''}}>100</option>
                                </select>
                        </div>

                        <div class="custom-control custom-switch ml-5 mb-2 mb-md-0">
                            <input type="checkbox" class="custom-control-input" id="trashedToggle"
                                {{$trashed ? 'checked' : ''}} onchange="toggleTrashed(this.checked)">
                            <label class="custom-control-label text-muted text-xs pt-1" for="trashedToggle">
                                Show deleted accounts
                            </label>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div id="selectedActions" class="d-flex justify-content-md-end align-items-center" style="display: none !important;">
                        <div class="badge badge-primary mr-2 px-3 py-2">
                            <i class="fas fa-check-circle mr-1"></i>
                            <span id="selectedCount">0</span> selected
                        </div>
                        <button class="btn btn-danger btn-sm" onclick="deleteSelected()">
                            <i class="fas fa-trash mr-1"></i>
                            Delete Selected
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th scope="col" class="border-top-0" width="50px">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="allCheck">
                                <label class="custom-control-label" for="allCheck"></label>
                            </div>
                        </th>
                        <th scope="col" class="border-top-0" width="80px">
                            <a href="{{request()->fullUrlWithQuery(['col' => 'id', 'dir' => $col == 'id' && $dir == 'asc' ? 'desc' : 'asc'])}}"
                                class="text-decoration-none text-dark font-weight-bold sort-header">
                                ID
                                <i class="fas fa-sort{{$col == 'id' ? '-' . ($dir == 'asc' ? 'up' : 'down') : ''}} ml-1 text-muted"></i>
                            </a>
                        </th>
                        <th scope="col" class="border-top-0">
                            <a href="{{request()->fullUrlWithQuery(['col' => 'username', 'dir' => $col == 'username' && $dir == 'asc' ? 'desc' : 'asc'])}}"
                                class="text-decoration-none text-dark font-weight-bold sort-header">
                                Username
                                <i class="fas fa-sort{{$col == 'username' ? '-' . ($dir == 'asc' ? 'up' : 'down') : ''}} ml-1 text-muted"></i>
                            </a>
                        </th>
                        <th scope="col" class="border-top-0 text-center" width="100px">
                            <span class="font-weight-bold text-muted">Posts</span>
                        </th>
                        <th scope="col" class="border-top-0 text-center" width="100px">
                            <span class="font-weight-bold text-muted">Followers</span>
                        </th>
                        <th scope="col" class="border-top-0 text-center" width="100px">
                            <span class="font-weight-bold text-muted">Following</span>
                        </th>
                        <th scope="col" class="border-top-0 text-center" width="200px">
                            <span class="font-weight-bold text-muted">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $key => $user)
                        @if(str_starts_with($user->status, 'delete'))
                        <tr class="user-row deleted-row">
                            <td class="align-middle">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" disabled>
                                    <label class="custom-control-label"></label>
                                </div>
                            </td>
                            <td class="align-middle">
                                <span class="badge badge-outline-danger">{{$user->id}}</span>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-wrapper mr-3">
                                        <img src="/storage/avatars/default.jpg" width="32" height="32" class="rounded-circle border" />
                                    </div>
                                    <div>
                                        <div class="font-weight-bold text-danger">{{$user->username}}</div>
                                        <small class="text-muted">Account deleted</small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center text-muted align-middle">—</td>
                            <td class="text-center text-muted align-middle">—</td>
                            <td class="text-center text-muted align-middle">—</td>
                            <td class="text-center align-middle">
                                <span class="badge badge-danger">
                                    <i class="fas fa-trash-alt mr-1"></i>
                                    Deleted
                                </span>
                            </td>
                        </tr>
                        @else
                        <tr class="user-row">
                            <td class="text-center align-middle">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" id="user_{{$key}}" class="custom-control-input action-check"
                                        data-id="{{$user->id}}" data-username="{{$user->username}}">
                                    <label class="custom-control-label" for="user_{{$key}}"></label>
                                </div>
                            </td>
                            <td class="text-center align-middle">
                                <a href="/i/admin/users/show/{{$user->id}}">
                                    <span class="badge badge-outline-primary">{{$user->id}}</span>
                                </a>
                            </td>
                            <td class="text-center align-middle">
                                <div class="d-flex align-items-center">
                                    <a href="/{{$user->account['username']}}" class="font-weight-bold" target="_blank">
                                        <div class="avatar-wrapper mr-3">
                                            @if($user->account)
                                            <img src="{{$user->account['avatar']}}" width="32" height="32" class="rounded-circle border"
                                                onerror="this.src='/storage/avatars/default.jpg';this.onerror=null;" />
                                            <div class="avatar-status bg-success"></div>
                                            @else
                                            <img src="/storage/avatars/default.jpg" width="32" height="32" class="rounded-circle border" />
                                            <div class="avatar-status bg-secondary"></div>
                                            @endif
                                        </div>
                                    </a>
                                    <div>
                                        <div class="d-flex">

                                            <div class="font-weight-bold">
                                                {{$user->username}}
                                                @if($user->is_admin)
                                                <span class="badge badge-danger badge-xs ml-1">
                                                    <i class="fas fa-crown mr-1"></i>Admin
                                                </span>
                                                @endif
                                            </div>
                                        </div>
                                        <small class="text-muted">{{ str_limit(strip_tags($user->profile->bio))}}</small>
                                        <small class="text-warning">{{ parse_url($user->profile->website, PHP_URL_HOST) }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center align-middle">
                                <a href="/i/web/profile/{{$user->account['id']}}" class="font-weight-bold" target="_blank">
                                    {{$user->account['statuses_count'] ?? 0}}
                                </a>
                            </td>
                            <td class="text-center align-middle">
                                <a href="/i/web/profile/{{$user->account['id']}}/followers" class="font-weight-bold" target="_blank">
                                    {{$user->account['followers_count'] ?? 0}}
                                </a>
                            </td>
                            <td class="text-center align-middle">
                                <a href="/i/web/profile/{{$user->account['id']}}/following" class="font-weight-bold" target="_blank">
                                    {{$user->account['following_count'] ?? 0}}
                                </a>
                            </td>
                            <td class="align-middle text-center">
                                <div class="btn-group btn-group-sm action-buttons" role="group">
                                    <a href="/i/admin/users/modtools/{{$user->id}}" class="btn btn-outline-warning btn-sm"
                                        title="Moderation Tools" data-toggle="tooltip">
                                        <i class="fas fa-tools"></i>
                                        <span class="font-weight-bold">Moderate</span>
                                    </a>
                                    @if($user->status !== 'deleted' && !$user->is_admin)
                                    <button class="btn btn-outline-danger btn-sm"
                                        title="Delete Account" data-toggle="tooltip" onclick="deleteAccount({{$user->id}})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-users fa-3x mb-3 d-block"></i>
                                    <h5>No users found</h5>
                                    <p class="mb-0">
                                        @if(request()->input('q'))
                                            Try adjusting your search criteria
                                        @else
                                            No users match the current filters
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->count() > 0)
        <div class="card-footer bg-lighter border-top-0">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Showing {{$users->count()}} results
                </div>
                <nav aria-label="User pagination">
                    <ul class="pagination pagination-sm mb-0">
                        @if($pagination['prev'] !== null)
                        <li class="page-item">
                            <a class="page-link" href="?page={{$pagination['prev']}}{{$pagination['query']}}" rel="prev">
                                <i class="fas fa-chevron-left"></i>
                                <span class="sr-only">Previous</span>
                            </a>
                        </li>
                        @else
                        <li class="page-item disabled">
                            <span class="page-link">
                                <i class="fas fa-chevron-left"></i>
                                <span class="sr-only">Previous</span>
                            </span>
                        </li>
                        @endif
                        <li class="page-item">
                            <a class="page-link" href="?page={{$pagination['next']}}{{$pagination['query']}}" rel="next">
                                <span class="sr-only">Next</span>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style type="text/css">
    .user-row {
        transition: all 0.2s ease;
    }
    .user-row:hover {
        background-color: rgba(0, 123, 255, 0.05);
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .deleted-row {
        opacity: 0.6;
        background-color: rgba(220, 53, 69, 0.05);
    }
    .deleted-row:hover {
        background-color: rgba(220, 53, 69, 0.1);
    }

    .avatar-wrapper {
        position: relative;
        display: inline-block;
    }

    .action-buttons {
        opacity: 0.7;
        transition: opacity 0.2s ease;
    }
    .user-row:hover .action-buttons {
        opacity: 1;
    }

    .sort-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem;
        margin: -0.75rem;
        border-radius: 0.25rem;
        transition: background-color 0.2s ease;
    }
    .sort-header:hover {
        background-color: rgba(0,0,0,0.05);
        text-decoration: none !important;
    }

    .badge-outline-primary {
        color: #007bff;
        border: 1px solid #007bff;
        background-color: transparent;
    }
    .badge-outline-danger {
        color: #dc3545;
        border: 1px solid #dc3545;
        background-color: transparent;
    }

    .card {
        border-radius: 0.5rem;
    }

    .custom-control-input:checked ~ .custom-control-label::before {
        background-color: #007bff;
        border-color: #007bff;
    }

    @media (max-width: 768px) {
        .btn-group-sm .btn {
            padding: 0.25rem 0.4rem;
        }
        .action-buttons .btn {
            margin-bottom: 2px;
        }
        .user-row:hover {
            transform: none;
            box-shadow: none;
        }
    }

    .input-group .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .badge {
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.8); }
        to { opacity: 1; transform: scale(1); }
    }
</style>
@endpush

@push('scripts')
<script type="text/javascript">
    const userSelectionManager = {
        selectedUsers: new Set(),

        init() {
            this.bindEvents();
            this.updateUI();
        },

        bindEvents() {
            const selectAllCheckbox = document.getElementById('allCheck');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', (e) => {
                    this.handleSelectAll(e.target.checked);
                });
            }

            const actionCheckboxes = document.querySelectorAll('.action-check:not([disabled])');
            actionCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', (e) => {
                    this.handleIndividualCheck(e.target);
                });
            });
        },

        handleSelectAll(checked) {
            const actionCheckboxes = document.querySelectorAll('.action-check:not([disabled])');

            if (checked) {
                actionCheckboxes.forEach(checkbox => {
                    checkbox.checked = true;
                    this.selectedUsers.add(checkbox.dataset.id);
                });
            } else {
                actionCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                this.selectedUsers.clear();
            }

            this.updateUI();
        },

        handleIndividualCheck(checkbox) {
            const userId = checkbox.dataset.id;

            if (checkbox.checked) {
                this.selectedUsers.add(userId);
            } else {
                this.selectedUsers.delete(userId);
            }

            this.updateSelectAllState();
            this.updateUI();
        },

        updateSelectAllState() {
            const selectAllCheckbox = document.getElementById('allCheck');
            const actionCheckboxes = document.querySelectorAll('.action-check:not([disabled])');
            const totalAvailable = actionCheckboxes.length;
            const selectedCount = this.selectedUsers.size;

            if (selectAllCheckbox) {
                if (selectedCount === 0) {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = false;
                } else if (selectedCount === totalAvailable) {
                    selectAllCheckbox.checked = true;
                    selectAllCheckbox.indeterminate = false;
                } else {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = true;
                }
            }
        },

        updateUI() {
            const selectedCount = this.selectedUsers.size;
            const selectedActions = document.getElementById('selectedActions');
            const selectedCountSpan = document.getElementById('selectedCount');

            if (selectedCount > 0) {
                selectedActions.style.display = 'flex';
                selectedCountSpan.textContent = selectedCount;
            } else {
                selectedActions.style.display = 'none';
            }
        },

        getSelectedUserData() {
            const selectedData = [];
            this.selectedUsers.forEach(userId => {
                const checkbox = document.querySelector(`[data-id="${userId}"]`);
                if (checkbox) {
                    selectedData.push({
                        id: userId,
                        username: checkbox.dataset.username
                    });
                }
            });
            return selectedData;
        },

        clearSelection() {
            this.selectedUsers.clear();
            const actionCheckboxes = document.querySelectorAll('.action-check');
            actionCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            this.updateSelectAllState();
            this.updateUI();
        }
    };

    $(document).ready(function() {
        $('[data-toggle="tooltip"]').tooltip();

        $('.human-size').each(function(d,a) {
            let el = $(a);
            let size = el.data('bytes');
            el.text(filesize(size, {round: 0}));
        });

        userSelectionManager.init();
    });

    function deleteAccount(id) {
        event.preventDefault();

        swal({
            title: 'Delete Account',
            text: "Are you sure you want to delete this account? This action cannot be undone.",
            icon: 'warning',
            dangerMode: true,
            buttons: {
                cancel: {
                    text: "Cancel",
                    value: false,
                    visible: true,
                },
                delete: {
                    text: "Delete",
                    value: "delete",
                    className: "btn-danger"
                }
            }
        })
        .then((willDelete) => {
            if (willDelete === 'delete') {
                swal({
                    title: 'Deleting...',
                    text: 'Please wait while we delete the account.',
                    icon: 'info',
                    buttons: false,
                    closeOnClickOutside: false,
                    closeOnEsc: false
                });

                axios.post('/i/admin/users/delete/' + id)
                .then(res => {
                    swal('Success!', 'Account has been deleted successfully.', 'success')
                    .then(() => {
                        window.location.reload();
                    });
                })
                .catch(err => {
                    swal('Error!', 'Failed to delete account. Please try again.', 'error');
                });
            }
        });
    }

    function changeLimit(limit) {
        const url = new URL(window.location);
        url.searchParams.set('limit', limit);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    function toggleTrashed(checked) {
        const url = new URL(window.location);
        if (checked) {
            url.searchParams.set('trashed', '1');
        } else {
            url.searchParams.delete('trashed');
        }
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    async function deleteSelected() {
        const selectedData = userSelectionManager.getSelectedUserData();

        if (selectedData.length === 0) {
            swal('No Selection', 'Please select at least one account to delete.', 'warning');
            return;
        }

        const usernames = selectedData.map(user => user.username);
        const ids = selectedData.map(user => user.id);

        swal({
            title: 'Bulk Delete Confirmation',
            text: `Are you sure you want to delete ${ids.length} account(s)?\n\nUsernames:\n${usernames.join('\n')}`,
            icon: 'warning',
            dangerMode: true,
            buttons: {
                cancel: {
                    text: "Cancel",
                    value: false,
                    visible: true,
                },
                delete: {
                    text: `Delete ${ids.length} Account(s)`,
                    value: "delete",
                    className: "btn-danger"
                }
            }
        })
        .then(async (res) => {
            if(res === 'delete') {
                swal({
                    title: 'Processing Deletion',
                    text: `Deleting ${ids.length} accounts... Please wait.`,
                    icon: 'info',
                    buttons: false,
                    closeOnClickOutside: false,
                    closeOnEsc: false
                });

                try {
                    await Promise.all(ids.map(id => deleteAccountById(id)));

                    swal({
                        title: 'Success!',
                        text: `Successfully deleted ${ids.length} account(s).`,
                        icon: 'success',
                        timer: 2000
                    });

                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } catch (error) {
                    swal('Error!', 'Some accounts could not be deleted. Please try again.', 'error');
                }
            }
        });
    }

    async function deleteAccountById(id) {
        return axios.post('/i/admin/users/delete/' + id);
    }
</script>
@endpush
