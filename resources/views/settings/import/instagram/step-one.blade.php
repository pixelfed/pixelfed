@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Import from Instagram</h3>
    <p class="lead">Step 1</p>
  </div>
  <hr>
  <section>
    <p class="lead">Before you proceed, you need to have a backup of your account from Instagram, you can do that <a href="https://www.instagram.com/download/request/">here</a>.</p>
  </section>
  <section class="mt-5 col-md-8 offset-md-2">
    <div class="card mb-3 step-one">
      <div class="card-body text-center">
        <p class="h5 font-weight-bold">Import <b>posts</b> directory</p>
        <p class="text-muted">The posts directory is inside the media directory. 250mb limit, if your posts directory exceeds that amount, you will have to wait until we support larger imports.</p>
        <hr>
        <form enctype="multipart/form-data" class="" method="post">
          @csrf
          <input type="file" name="posts[]" multiple="" directory="" webkitdirectory="" mozdirectory="" accept="image/*">
          <button type="submit" class="mt-4 btn btn-primary btn-block font-weight-bold">Upload Posts</button>
        </form>
      </div>
    </div>
  </section>

@endsection
