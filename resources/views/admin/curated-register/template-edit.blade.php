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

        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                @if (session('status'))
                    <div class="alert alert-success font-weight-bold lead" id="shm">
                        {{ session('status') }}
                    </div>
                    <script>
                        setTimeout(() => document.getElementById('shm').classList.add('animate__animated', 'animate__bounceOutLeft'), 2000);
                        setTimeout(() => document.getElementById('shm').style.display = 'none', 2500);
                    </script>
                @endif
                <div class="card">
                    <div class="card-body">
                        <h2 class="display-4 mb-0">Edit Template</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                @foreach ($errors->all() as $error)
                                    <p class="font-weight-bold mb-0">{{ $error }}</p>
                                @endforeach
                            </div>
                        @endif
                        <form method="post" id="updateForm">
                            @csrf

                            <div class="form-group">
                                <label class="small font-weight-bold">Shortcut/Name</label>
                                <input
                                    class="form-control"
                                    name="name"
                                    value="{{$template->name}}"
                                    placeholder="An optional name/shortcut for easy access" />
                            </div>

                            <div class="form-group">
                                <label class="small font-weight-bold">Content</label>
                                <textarea
                                    class="form-control"
                                    name="content"
                                    rows="{{$template->content && strlen($template->content) > 500 ? 16 : 5}}"
                                    placeholder="Add your custom message template here...">{{$template->content}}</textarea>
                            </div>

                            @if($template->description == null)
                            <p class="font-weight-bold">
                                <a class="font-weight-bold small" data-toggle="collapse" href="#collapseDescription" aria-expanded="false" aria-controls="collapseDescription">
                                    Add optional description
                                </a>
                            </p>
                            @endif

                            <div class="collapse {{ $template->description === null ? '':'show'}}" id="collapseDescription">
                                <div class="form-group">
                                    <label class="small font-weight-bold">Description</label>
                                    <textarea
                                        class="form-control"
                                        name="description"
                                        rows="4"
                                        placeholder="Add an optional description that is only visible to admins...">{{ $template->description }}</textarea>
                                </div>
                            </div>

                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="active" name="active" {{ $template->is_active ? 'checked' : ''}}>
                                <label class="custom-control-label font-weight-bold" for="active">Mark as Active</label>
                            </div>

                            <hr>
                            <div class="d-flex">
                                <button type="button" class="btn btn-primary flex-grow-1 rounded-pill font-weight-bold" id="saveBtn">Save</button>
                                <button type="button" class="btn btn-danger rounded-pill font-weight-bold" id="deleteBtn">Delete</button>
                            </div>
                        </form>
                        <form method="post" id="deleteForm">
                            @method('DELETE')
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
    $('#saveBtn').click(() => {
        $('#updateForm').submit()
    })
    $('#deleteBtn').click(() => {
        swal({
            title: 'Confirm Deletion',
            text: 'Are you sure you want to delete this template? It will not be recoverable',
            icon: 'warning',
            dangerMode: true,
            buttons: {
                close: {
                    text: "Close",
                    value: "close",
                    close: true,
                    className: "swal-button--cancel"
                },
                confirm: {
                    text: "Delete",
                    value: "delete",
                    className: "btn-danger"
                }
            }
        }).then(res => {
            if(res == 'delete') {
                $('#deleteForm').submit();
                // window.location.href = '/i/admin/curated-onboarding/templates';
            }
        })
    })
</script>
@endpush
