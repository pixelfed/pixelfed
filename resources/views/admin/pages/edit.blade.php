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
      <span class="pl-1"><a href="#" class="font-weight-bold">Edit</a></span>
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
        <a class="btn btn-light font-weight-bold py-0" href="#">Set Expire Date</a>
      </div>
      <div>
        <a class="btn btn-light font-weight-bold py-0" href="#">Preview</a>
        <a class="btn btn-primary font-weight-bold py-0 btn-save" href="#">Save</a>
      </div>
    </div>
  </div>

@endsection

@push('styles')
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<style type="text/css">
.ql-container {
    box-sizing: border-box;
    font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",
                 Roboto,Helvetica,Arial,sans-serif;
    font-size: 16px;
    height: 100%;
    margin: 0px;
    position: relative;
}
</style>
@endpush
@push('scripts')
<!-- Include the Quill library -->
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<!-- Initialize Quill editor -->
<script>
  window.editor = new Quill('#editor', {
    theme: 'snow'
  });
  $('.btn-save').on('click', function(e) {
    e.preventDefault();
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

  $('#title').on('change input', function(e) {
    e.preventDefault();
    let title = this.value.split(' ').join('-').toLowerCase();
  })
</script>
@endpush