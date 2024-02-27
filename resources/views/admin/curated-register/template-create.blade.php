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
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="display-4">Create Template</h2>
                        <p class="lead my-0">Create re-usable templates of messages and application requests.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                @foreach ($errors->all() as $error)
                                    <p class="font-weight-bold mb-0">{{ $error }}</p>
                                @endforeach
                            </div>
                        @endif
                        <form method="post">
                            @csrf

                            <div class="form-group">
                                <label class="small font-weight-bold">Shortcut/Name</label>
                                <input
                                    class="form-control"
                                    name="name"
                                    value="{{old('name')}}"
                                    placeholder="An optional name/shortcut for easy access" />
                            </div>

                            <div class="form-group">
                                <label class="small font-weight-bold">Content</label>
                                <textarea
                                    class="form-control"
                                    name="content"
                                    value="{{old('content')}}"
                                    rows="8"
                                    placeholder="Add your custom message template here..."></textarea>
                            </div>

                            <p class="font-weight-bold">
                                <a class="font-weight-bold small" data-toggle="collapse" href="#collapseDescription" aria-expanded="false" aria-controls="collapseDescription">
                                    Add optional description
                                </a>
                            </p>

                            <div class="collapse" id="collapseDescription">
                                <div class="form-group">
                                    <label class="small font-weight-bold">Description</label>
                                    <textarea
                                        class="form-control"
                                        name="description"
                                        rows="4"
                                        placeholder="Add an optional description that is only visible to admins..."></textarea>
                                </div>
                            </div>

                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="active" name="active" checked>
                                <label class="custom-control-label font-weight-bold" for="active">Mark as Active</label>
                            </div>

                            <hr>
                            <button class="btn btn-primary btn-block rounded-pill font-weight-bold">Create Template</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection
