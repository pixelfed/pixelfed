@extends('layouts.app')

@section('content')
    <div class="jumbotron jumbotron-fluid bg-pixelfed text-white my-0">
      <div class="container text-center my-5 py-5">
        <h1 class="display-4">Federated Image Sharing</h1>
        <p class="lead">Powered by <a href="https://en.wikipedia.org/wiki/ActivityPub" class="text-white font-weight-bold">ActivityPub</a> and the <a href="https://en.wikipedia.org/wiki/Fediverse" class="text-white font-weight-bold">fediverse</a>.</p>
      </div>
    </div>
@endsection

@push('meta')
<meta property="og:description" content="Federated Image Sharing">
@endpush
