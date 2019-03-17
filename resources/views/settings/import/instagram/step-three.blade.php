@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Import from Instagram</h3>
    <p class="lead">Step 3</p>
  </div>
  <hr>
  <section class="mt-5 col-md-8 offset-md-2">
    <div class="card mb-3 step-three">
      <div class="card-body text-center">
        <p class="h5 font-weight-bold">Found {{$job->files->count()}} posts to import</p>
        <p class="text-muted"></p>
        <hr>
        <form enctype="multipart/form-data" class="" method="post">
          @csrf
          <button type="submit" class="mt-4 btn btn-primary btn-block font-weight-bold">Import All</button>
        </form>
      </div>
      </div>
    </div>
  </section>

@endsection