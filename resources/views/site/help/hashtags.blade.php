@extends('site.help.partial.template', ['breadcrumb'=>'Hashtags'])

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Hashtags</h3>
  </div>
  <hr>
  <p class="lead">A hashtag — written with a # symbol — is used to index keywords or topics.</p>
  <div class="py-4">
    <p class="font-weight-bold h5 pb-3">Using hashtags to categorize posts by keyword</p>
    <ul>
      <li class="mb-3 ">People use the hashtag symbol (#) before a relevant phrase or keyword in their post to categorize those posts and make them more discoverable.</li>
      <li class="mb-3 ">Any hashtags will be linked to a hashtag page with other posts containing the same hashtag.</li>
      <li class="mb-3">Hashtags can be used anywhere in a post.</li>
      <li class="">You can add up to 30 hashtags to your post or comment.</li>
    </ul>
  </div>
  <div class="py-4">
  <p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse0" role="button" aria-expanded="false" aria-controls="collapse0">
      <i class="fas fa-chevron-down mr-2"></i>
      How do I use a hashtag on Pixelfed?
    </a>
    <div class="collapse" id="collapse0">
      <div>
        <ul>
          <li>You can add hashtags to post captions, if the post is public the hashtag will be discoverable.</li>
          <li>You can follow hashtags on Pixelfed to stay connected with interests you care about.</li>
        </ul>
      </div>
    </div>
  </p>
  <p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse1" role="button" aria-expanded="false" aria-controls="collapse1">
      <i class="fas fa-chevron-down mr-2"></i>
      How do I follow a hashtag?
    </a>
    <div class="collapse" id="collapse1">
      <div>
        <p>You can follow hashtags on Pixelfed to stay connected with interests you care about.</p>
        <p class="mb-0">To follow a hashtag:</p>
        <ol>
          <li>Tap any hashtag (example: #art) you see on Pixelfed.</li>
          <li>Tap <span class="font-weight-bold">Follow</span>. Once you follow a hashtag, you'll see its photos and videos appear in feed.</li>
        </ol>
        <p>To unfollow a hashtag, tap the hashtag and then tap Unfollow to confirm.</p>
        <p class="mb-0">
          You can follow up to 20 hashtags per hour or 100 per day.
        </p>
      </div>
    </div>
  </p>
  </div>
  <hr>
  <div class="card bg-primary border-primary" style="box-shadow: none !important;border: 3px solid #08d!important;">
    <div class="card-header text-light font-weight-bold h4 p-4 bg-primary">Hashtag Tips</div>
    <div class="card-body bg-white p-3">
      <ul class="pt-3">
        <li class="lead  mb-4">You cannot add spaces or punctuation in a hashtag, or it will not work properly.</li>
        <li class="lead  mb-4">Any public posts that contain a hashtag may be included in search results or discover pages.</li>
        <li class="lead ">You can search hashtags by typing in a hashtag into the search bar.</li>

      </ul>
    </div>
  </div>
@endsection