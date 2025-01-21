@extends('admin.partial.template-full')

@section('section')
</div>
<div class="header bg-primary pb-3 mt-n4">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <p class="display-1 text-white d-inline-block mb-0">Custom CSS</p>
                    <p class="lead mb-0 text-white">Customize your instance with custom css.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid mt-4">
    <div class="col-12 col-md-6">
        <form method="post">
            @csrf
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input
                        type="checkbox"
                        name="show"
                        class="custom-control-input"
                        id="customCheck1"
                        {{ (bool) config_cache('uikit.show_custom.css') ? 'checked' : null}}
                    >
                    <label class="custom-control-label" for="customCheck1">Enable Custom CSS</label>
                </div>
            </div>
            <div class="form-group">
                <label for="css" class="font-weight-bold">Custom CSS</label>
                <textarea
                    class="form-control"
                    id="css"
                    name="css"
                    rows="5">{!!config_cache('uikit.custom.css')!!}</textarea>
            </div>
            <button class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
@endsection
