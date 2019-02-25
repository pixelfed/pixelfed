@extends('site.partial.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Fediverse</h3>
  </div>
  <hr>
  <section>
    <p class="lead">Fediverse is a portmanteau of “federation” and “universe”. It is a common, informal name for a somewhat broad federation of social network servers.</p>
    <p class="lead font-weight-bold text-muted mt-4">Supported Fediverse Projects</p>
    <ul class="lead pl-4">
      <li><a href="https://joinmastodon.org" rel="nofollow noopener">Mastodon</a> – A federated twitter alternative.</li>
      <li><a href="https://anfora.app/" rel="nofollow noopener">Anfora</a> – Self-hosted photo gallery social network.</li>
    </ul>
  </section>
@endsection

@push('meta')
<meta property="og:description" content="What is the Fediverse">
@endpush
