@extends('admin.partial.template-full')

@section('section')
</div>
<div class="header bg-primary pb-3 mt-n4">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <p class="display-1 text-white">Edit Page</p>
                    <p class="lead text-white mt-n4 mb-0">{{$page->slug}}</p>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid mt-4">
    <input type="hidden" id="slug" name="slug" value="{{$page->slug}}">
    <input class="form-control form-control-lg" id="title" name="title" placeholder="Title">
    <p class="small text-muted">
      Page URL: <span class="page-url font-weight-bold">{{$page->url()}}</span>
      {{-- <span class="pl-1"><a href="#" class="font-weight-bold">Edit</a></span> --}}
    </p>
    <div id="editor" class="d-none" style="height: 400px">
      {!!$page->content!!}
    </div>
    <div id="rawEditor" style="height: 400px">
      <label class="font-weight-bold">Raw HTML</label>
      <textarea class="form-control" rows="8" id="rawText" v-pre>{{$page->content}}</textarea>
    </div>
    <div class="mt-3 d-flex justify-content-between">
      <div>
        <div class="custom-control custom-switch">
          <input type="checkbox" class="custom-control-input" id="activeSwitch" {{$page->active?'checked="true"':''}}>
          <label class="custom-control-label font-weight-bold" for="activeSwitch">Active</label>
        </div>
        {{-- <a class="btn btn-light font-weight-bold py-0" href="#">Set Expire Date</a> --}}
      </div>
      <div>
        {{-- <a class="btn btn-light font-weight-bold py-0" href="#">Preview</a> --}}
        <a class="btn btn-outline-danger font-weight-bold py-0 btn-delete" href="#">Delete</a>
        <a class="btn btn-primary font-weight-bold py-0 btn-save" href="#">Save</a>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style type="text/css">
.ql-container {
    box-sizing: border-box;
    font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
    font-size: 16px;
    height: 100%;
    margin: 0px;
    position: relative;
}
.custom-control {
    padding-left: 3.5rem;
}
</style>
@endpush
@push('scripts')
<script>
    window.useRaw = true;

    $('.btn-save').on('click', function(e) {
        e.preventDefault();
        let confirm = window.confirm('Are you sure you want to save this page?');
        if(confirm !== true) {
            return;
        }
        let html = window.useRaw ?
        $('#rawText').val() :
        editor.root.innerHTML;
        let title = $('#title').val();
        let active = $('#activeSwitch')[0].checked;
        axios.post(window.location.href, {
            slug: '{{$page->slug}}',
            title: title,
            content: html,
            active: active
        }).then((res) => {
            window.location.href = '{{$page->url()}}';
        }).catch((err) => {
            console.log(err)
        });
    });

    $('.btn-delete').on('click', function(e) {
        e.preventDefault();
        let confirm = window.confirm('Are you sure you want to delete this page?');
        if(confirm == true) {
            axios.post('/i/admin/settings/pages/delete', {
                id: '{{$page->id}}'
            }).then(res => {
                window.location.href = '/i/admin/settings/pages';
            }).catch(err => {
                swal('Error', 'An error occured!', 'error');
                console.log(err);
            });
        }
    });

    $('#title').on('change input', function(e) {
        e.preventDefault();
        let title = this.value.split(' ').join('-').toLowerCase();
    })
</script>
@endpush
