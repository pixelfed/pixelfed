@extends('site.help.partial.template', ['breadcrumb'=>'Community Guidelines'])

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Community Guidelines</h3>
  </div>
  <hr>
  @if($page)
  <div>
    {!!$page->content!!}
    <hr>
    <p class="">This document was last updated {{$page->created_at->format('M d, Y')}}.</p>
  </div>
  @else
  <div>
    <p class="lead mb-5">The following guidelines are not a legal document, and final interpretation is up to the administration of {{config('pixelfed.domain.app')}}; they are here to provide you with an insight into our content moderation policies:</p>
    <div class="py-4">
      <h5 class="pb-3">The following types of content will be removed from the public timeline:</h5>
      <ul>
        <li class="mb-3">Excessive advertising</li>
        <li class="mb-3">Uncurated news bots posting from third-party news sources</li>
        <li class="mb-3">Untagged nudity, pornography and sexually explicit content, including artistic depictions</li>
        <li class="mb-3">Untagged gore and extremely graphic violence, including artistic depictions</li>
      </ul>
    </div>
    <hr>
    <div class="py-4">
      <h5 class="pb-3">The following types of content will be removed from the public timeline, and may result in account suspension and revocation of access to the service:</h5>
      <ul>
        <li class="mb-3">Racism or advocation of racism</li>
        <li class="mb-3">Sexism or advocation of sexism</li>
        <li class="mb-3">Discrimination against gender and sexual minorities, or advocation thereof</li>
        <li class="mb-3">Xenophobic and/or violent nationalism</li>
      </ul>
    </div>
    <hr>
    <div class="py-4">
      <h5 class="pb-3">The following types of content are explicitly disallowed and will result in revocation of access to the service:</h5>
      <ul>
        <li class="mb-3">Sexual depictions of children</li>
        <li class="mb-3">Content illegal in Canada, Germany and/or France, such as holocaust denial or Nazi symbolism</li>
        <li class="mb-3">Conduct promoting the ideology of National Socialism</li>
      </ul>
    </div>
    <hr>
    <div class="py-4">
      <h5 class="pb-3">Any conduct intended to stalk or harass other users, or to impede other users from utilizing the service, or to degrade the performance of the service, or to harass other users, or to incite other users to perform any of the aforementioned actions, is also disallowed, and subject to punishment up to and including revocation of access to the service. This includes, but is not limited to, the following behaviors:</h5>
      <ul>
        <li class="mb-3">Continuing to engage in conversation with a user that has specifically has requested for said engagement with that user to cease and desist may be considered harassment, regardless of platform-specific privacy tools employed.</li>
        <li class="mb-3">Aggregating, posting, and/or disseminating a person's demographic, personal, or private data without express permission (informally called doxing or dropping dox) may be considered harassment.</li>
        <li class="mb-3">Inciting users to engage another user in continued interaction or discussion after a user has requested for said engagement with that user to cease and desist (informally called brigading or dogpiling) may be considered harassment.</li>
      </ul>
    </div>
    <hr>
    <p>These provisions notwithstanding, the administration of the service reserves the right to revoke any user's access permissions, at any time, for any reason, except as limited by law.</p>
    <hr>
    <p class="">This document was last updated Jun 26, 2019.</p>
    <p class="">Originally adapted from the <a href="https://mastodon.social/about/more">Mastodon</a> Code of Conduct.</p>
</div>
  @endif
@endsection
