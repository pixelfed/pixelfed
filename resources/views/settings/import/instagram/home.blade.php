@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Import from Instagram</h3>
  </div>
  <hr>
  <section>
    <div class="alert alert-info">
      <p class="mb-0 font-weight-bold">You can download an Instagram backup <a href="https://www.instagram.com/download/request/">here</a>.</p>
    </div>
    <p class="lead font-weight-bold mb-1">Requirements:</p>
    <ul class="lead mb-4">
      <li>media/posts directory</li>
      <li>content/post_1.json file</li>
    </ul>
    <p class="lead font-weight-bold mb-1">Process:</p>
    <ol class="lead mb-4">
      <li>Upload posts directory</li>
      <li>Upload posts_1.json file</li>
      {{-- <li>Confirm each post</li> --}}
      <li>Import Data</li>
    </ol>
    <form method="post">
      @csrf
      <p>
        <button type="submit" class="btn btn-outline-primary btn-block font-weight-bold py-1">Start Import</button>
      </p>
    </form>
  </section>


@endsection
