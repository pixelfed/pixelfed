@extends('admin.partial.template')

@include('admin.settings.sidebar')

@section('section')
  <div class="title">
    <h3 class="font-weight-bold">Edit Page</h3>
    <p class="lead">{{$page->slug}}</p>
  </div>
  <hr>

  <div>
    <input type="hidden" id="slug" name="slug" value="{{$page->slug}}">
    <input class="form-control form-control-lg" id="title" name="title" placeholder="Title">
    <p class="small text-muted">
      Page URL: <span class="page-url font-weight-bold">{{$page->url()}}</span>
      {{-- <span class="pl-1"><a href="#" class="font-weight-bold">Edit</a></span> --}}
    </p>
    <div id="editor" style="height: 400px">
      {!!$page->content!!}
    </div>
    <div class="mt-3 d-flex justify-content-between">
      <div>
        <div class="custom-control custom-switch d-inline pr-3">
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

@endsection

@push('styles')
<link rel="stylesheet" href="{{mix('css/quill.css')}}"/>
<style type="text/css">
.ql-container {
    box-sizing: border-box;
    font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",
                 Roboto,Helvetica,Arial,sans-serif;
    font-size: 16px;
    height: 100%;
    position: relative;
}
</style>
@endpush
@push('scripts')
<script src="{{mix('js/quill.js')}}"></script>

<script>
  window.editor = new Quill('#editor', {
    theme: 'snow'
  });
  $('.btn-save').on('click', function(e) {
    e.preventDefault();
    let confirm = window.confirm('Are you sure you want to save this page?');
    if(confirm !== true) {
      return;
    }
    let html = editor.container.firstChild.innerHTML;
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