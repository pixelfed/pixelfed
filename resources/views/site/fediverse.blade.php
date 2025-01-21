@extends('site.partial.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Fediverse</h3>
  </div>
  <hr>
  <section>
    <p class="lead"><a href="http://fediverse.party/" rel="nofollow noopener">Fediverse</a> {{__('site.is_a_portmanteau_of_federation_and_universe_etc')}}</p>
    <p class="lead font-weight-bold text-muted mt-4 mb-0">{{__('site.supported_fediverse_projects')}}</p>
    <p class="small text-muted">{{__('site.some_of_the_better_known_fediverse_projects_include')}}</p>
    <ul class="lead pl-4">
      <li><a href="https://joinmastodon.org" rel="nofollow noopener">Mastodon</a> – {{__('site.a_federated_microblogging_alternative')}}</li>
    </ul>
  </section>
@endsection

@push('meta')
<meta property="og:description" content="Fediverse {{__('site.is_a_portmanteau_of_federation_and_universe_etc')}}">
@endpush
