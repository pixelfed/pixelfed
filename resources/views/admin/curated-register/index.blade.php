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
        <div class="select-all-menu" style="display: none;">
            <form class="form-inline" id="select-form">
                <p class="mb-2 align-middle text-sm mr-2 font-weight-bold text-muted">Select an action:</p>
                <label class="sr-only" for="actionInput">Action</label>
                <select class="custom-select mb-2 mr-sm-2" id="actionInput">
                    <option selected disabled>Select action</option>
                    <option value="silently-reject-all" data-action="silently reject">Silently reject all</option>
                    <option value="email-reject-all" data-action="reject all w/ email">Reject all w/ email</option>
                    <option disabled value="">-------------------</option>
                    <option value="request-info" data-action="request more info">Request more info</option>
                    <option disabled value="">-------------------</option>
                    <option value="approve-all" data-action="approve all">Approve all</option>
                </select>
                <button type="submit" class="btn btn-primary mb-2" id="actionInputButton" disabled>Submit</button>
            </form>
        </div>
        <div class="table-responsive rounded">
            <table class="table table-dark">
                <thead class="thead-dark">
                    <tr>
                        <th scope="col">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="selectall">
                                <label class="custom-control-label" for="selectall"></label>
                            </div>
                        </th>
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
                        <td>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input account-select-check" id="r{{$record->id}}" data-id="{{$record->id}}">
                                <label class="custom-control-label" for="r{{$record->id}}"></label>
                            </div>
                        </td>
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

@push('scripts')
<script type="text/javascript">
    const menu = document.querySelector(".select-all-menu");
    const selectAll = document.getElementById("selectall");
    const checkboxes = document.querySelectorAll(".account-select-check");
    const form = document.getElementById('select-form');
    const action = document.getElementById('actionInput');
    const actionBtn = document.getElementById('actionInputButton');

    action.addEventListener("change", function() {
        if([
            'silently-reject-all',
            'email-reject-all',
            'request-info',
            'approve-all'
        ].includes(action.value)) {
            actionBtn.removeAttribute('disabled')
        } else {
            actionBtn.setAttribute('disabled', 'disabled')
        }
    })

    selectAll.addEventListener("change", function() {
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = selectAll.checked;
        });

        if(selectAll.checked) {
            menu.style.display = 'block';
        } else {
            menu.style.display = 'none';
        }
    });

    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener("change", function() {
            let count = Array.from(checkboxes).filter(cb => cb.checked).length;

            if(count > 1) {
                menu.style.display = 'block';
            } else {
                menu.style.display = 'none';
            }
        })
    })

    form.addEventListener("submit", async function(event) {
        event.preventDefault();
        event.currentTarget?.blur();

        const messages = await axios.get('/i/admin/api/curated-onboarding/templates/get')
        .then(res => res.data);

        let action = form.actionInput.value;
        if(action === 'Select action') {
            return;
        }

        let actionMap = {
            'silently-reject-all': 'silently reject all these applications?',
            'email-reject-all': 'reject and notify applicants via email?',
            'request-info': 'request more info from these applicants?',
            'approve-all': 'approve all these applicants?',
        }

        let wrapper = document.createElement('div');
        let warning = document.createElement('p');
        warning.classList.add('text-left');
        warning.innerHTML = 'Are you sure you want to ' + actionMap[action];
        wrapper.appendChild(warning)

        swal({
            title: "Confirm",
            content: wrapper,
            icon: "warning",
            dangerMode: true,
            buttons: {
                cancel: "Cancel",
                confirm: {
                  text: "Proceed",
                  value: "proceed",
                }
            },
        })
        .then(res => {
            if(res === 'proceed') {
                const ids = Array.from(checkboxes).filter(cb => cb.checked).map(el => el.getAttribute('data-id'));

                if(action === 'request-info') {
                    let wrapper = document.createElement('div');
                    let warning = document.createElement('p');
                    wrapper.classList.add('list-group')
                    warning.classList.add('text-left');
                    warning.innerHTML = 'Select a message to send:';
                    wrapper.appendChild(warning);

                    messages.forEach(m => {
                        let mr = document.createElement('button');
                        mr.classList.add('list-group-item');
                        mr.innerHTML = m.name;
                        mr.onclick = () => {
                            swal.close();
                            handleRequestType(m, ids)
                        }
                        wrapper.appendChild(mr);
                    })

                    swal({
                        title: "Select Message Template",
                        content: wrapper,
                        icon: "warning",
                        dangerMode: true,
                        buttons: false,
                    })
                    return;
                } else {
                    ids.reduce((promiseChain, id) => {
                        return promiseChain
                        .then((result) => {
                            return handleAction(id, action);
                        })
                        .catch((error) => {
                            throw error;
                        });
                    }, Promise.resolve())
                    .then(() => {
                        console.log('All requests completed successfully');
                        window.location.reload()
                    })
                    .catch((error) => {
                        swal('Error', 'An error occured, please try again later!', 'error');
                        console.error('Promise chain failed:', error);
                    });
                }
            }
        })
    });

    function handleRequestType(type, ids) {
        swal({
            title: 'Confirm Message Request',
            text: type.content,
            icon: "warning",
            dangerMode: true,
            buttons: {
                cancel: "Cancel",
                confirm: {
                  text: "Proceed",
                  value: "proceed",
                }
            }
        }).then(res => {
            if(res === 'proceed') {
                ids.reduce((promiseChain, id) => {
                    return promiseChain
                    .then((result) => {
                        return handleAction(id, 'request-info', type.content);
                    })
                    .catch((error) => {
                        console.error(`Error fetching data for ID ${id}:`, error);
                        throw error;
                    });
                }, Promise.resolve())
                .then(() => {
                    console.log('All requests completed successfully');
                    window.location.reload()
                })
                .catch((error) => {
                    swal('Error', 'An error occured, please try again later!', 'error');
                    console.error('Promise chain failed:', error);
                });
            } else {
                console.log(res)
            }
        })
    }

    function handleAction(id, type, msg = false) {
        if(type === 'silently-reject-all') {
            return axios.post(`/i/admin/api/curated-onboarding/show/${id}/reject`, {
                action: 'reject-silent'
            })
        } else if(type === 'email-reject-all') {
            return axios.post(`/i/admin/api/curated-onboarding/show/${id}/reject`, {
                action: 'reject-email'
            })
        } else if(type === 'approve-all') {
            return axios.post(`/i/admin/api/curated-onboarding/show/${id}/approve`)
        } else if(type === 'request-info') {
            axios.post(`/i/admin/api/curated-onboarding/show/${id}/message/send`, {
                message: msg
            })
        }
    }
</script>
@endpush
