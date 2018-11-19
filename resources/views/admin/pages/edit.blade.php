@extends('admin.partial.template')

@section('section')
  <div class="title">
    <h3 class="font-weight-bold">Edit Page</h3>
    <p class="lead">{{request()->query('page')}}</p>
  </div>
  <hr>

  <div>
    <div id="editor" style="height: 400px">
      <p class="lead">PixelFed is a federated image sharing platform, powered by the <a href="#">ActivityPub</a> protocol.</p>
    </div>
    <p class="mt-3 text-right mb-0">
      <a class="btn btn-primary font-weight-bold" href="#">Save</a>
    </p>
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
  var quill = new Quill('#editor', {
    theme: 'snow'
  });
</script>
@endpush