@extends('site.partial.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">About</h3>
  </div>
  <hr>
  <section>
    <p class="lead">PixelFed is a federated image sharing platform, powered by the <a href="#">ActivityPub</a> protocol.</p>
  </section>
@endsection

@push('meta')
<meta property="og:description" content="PixelFed is a federated image sharing platform, powered by the ActivityPub protocol.">
@endpush
