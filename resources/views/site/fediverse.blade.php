@extends('site.partial.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Fediverse</h3>
  </div>
  <hr>
  <section>
    <p class="lead"><a href="http://fediverse.party/" rel="nofollow noopener">Fediverse</a> is a portmanteau of “federation” and “universe”. It is a common, informal name for a federation of social network servers, specializing in different types of media.</p>
    <p class="lead font-weight-bold text-muted mt-4 mb-0">Supported Fediverse Projects</p>
    <p class="small text-muted">Some of the better known fediverse projects include:</p>
    <ul class="lead pl-4">
      <li><a href="https://joinmastodon.org" rel="nofollow noopener">Mastodon</a> – A federated microblogging alternative.</li>
    </ul>
  </section>
@endsection

@push('meta')
<meta property="og:description" content="Fediverse is a portmanteau of “federation” and “universe”. It is a common, informal name for a federation of social network servers, specializing in different types of media.">
@endpush
