@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Import from Instagram</h3>
    <p class="lead">Step 2</p>
  </div>
  <hr>
  <section class="mt-5 col-md-8 offset-md-2">
    <div class="card mb-3 step-two">
      <div class="card-body text-center">
        <p class="h5 font-weight-bold">Import <b>posts_1.json</b> file</p>
        <p class="text-muted">10mb limit, please only upload the posts_1.json file found in the content directory</p>
        <hr>
        <form enctype="multipart/form-data" class="" method="post">
          @csrf
          <input type="file" name="posts_1">
          <button type="submit" class="mt-4 btn btn-primary btn-block font-weight-bold">Upload posts_1.json</button>
        </form>
      </div>
      </div>
    </div>
  </section>

@endsection
