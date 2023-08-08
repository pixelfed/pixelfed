@extends('site.help.partial.template', ['breadcrumb'=>'Your Profile'])

@section('section')


  <div class="title">
    <h3 class="font-weight-bold">Your Profile</h3>
  </div>
  <hr>
  <p class="h5 text-muted ">Edit</p>
  <p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse1" role="button" aria-expanded="false" aria-controls="collapse1">
      <i class="fas fa-chevron-down mr-2"></i>
      How do I edit my bio, name, email, or password?
    </a>
    <div class="collapse" id="collapse1">
      <div>
        To edit your account using a web browser:
        <ol class="">
          <li>Go to <a href="{{route('settings')}}">{{route('settings')}}</a>.</li>
          <li>You should see the <span class="font-weight-bold">Name</span>, <span class="font-weight-bold">Website</span>, and  <span class="font-weight-bold">Bio</span> fields.</li>
          <li>Change the desired fields, and then click the <span class="font-weight-bold">Submit</span> button.</li>
        </ol>
      </div>
    </div>
  </p>
  <p> 
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse2" role="button" aria-expanded="false" aria-controls="collapse2">
      <i class="fas fa-chevron-down mr-2"></i>
      Why can't I update my username?
    </a>
    <div class="collapse" id="collapse2">
      <div>
        Pixelfed is a federated application, changing your username is not supported in every <a href="https://en.wikipedia.org/wiki/Federated_architecture">federated software</a> so we cannot allow username changes. Your best option is to create a new account with your desired username.
      </div>
    </div>
  </p>
  <hr>
  <p class="h5 text-muted ">Privacy</p>
  <p> 
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse3" role="button" aria-expanded="false" aria-controls="collapse3">
      <i class="fas fa-chevron-down mr-2"></i>
      How do I set my photos and videos to private so that only approved followers can see them?
    </a>
    <div class="collapse" id="collapse3">
      <div>
        To change your account visibility:
        <ol class="">
          <li>Go to <a href="{{route('settings.privacy')}}">{{route('settings.privacy')}}</a>.</li>
          <li>Check the <span class="font-weight-bold">Private Account</span> checkbox.</li>
          <li>The confirmation modal will popup and ask you if you want to keep existing followers and disable new follow requests</li>
          <li>Click the <span class="font-weight-bold">Submit</span> button.</li>
        </ol>
      </div>
    </div>
  </p>
  {{-- <p> 
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse4" role="button" aria-expanded="false" aria-controls="collapse4">
      <i class="fas fa-chevron-down mr-2"></i>
      Who can like, share or comment on my photos and videos?
    </a>
    <div class="collapse" id="collapse4">
      <div>
        It depends on the visibility of your post.
      </div>
    </div>
  </p> --}}
  {{-- <p> 
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse5" role="button" aria-expanded="false" aria-controls="collapse5">
      <i class="fas fa-chevron-down mr-2"></i>
      How do I filter out comments that I don't want to appear on my posts?
    </a>
    <div class="collapse" id="collapse5">
      <div>

      </div>
    </div>
  </p> --}}  
  {{-- <p> 
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse6" role="button" aria-expanded="false" aria-controls="collapse6">
      <i class="fas fa-chevron-down mr-2"></i>
      Who can see my posts?
    </a>
    <div class="collapse" id="collapse6">
      <div>
        You can update your account by visiting the <a href="{{route('settings')}}">account settings</a> page.
      </div>
    </div>
  </p> --}}
  {{-- <p> 
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse7" role="button" aria-expanded="false" aria-controls="collapse7">
      <i class="fas fa-chevron-down mr-2"></i>
      Who can see my private posts if I add a hashtag?
    </a>
    <div class="collapse" id="collapse7">
      <div>
        You can update your account by visiting the <a href="{{route('settings')}}">account settings</a> page.
      </div>
    </div>
  </p> --}}
  <hr>
  <p class="h5 text-muted " id="security">Security</p>
  <p> 
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#sec-collapse8" role="button" aria-expanded="false" aria-controls="sec-collapse8">
      <i class="fas fa-chevron-down mr-2"></i>
      How can I secure my account?
    </a>
    <div class="collapse" id="sec-collapse8">
      <div>
        Here are some recommendations to keep your account secure:
        <ul class="">
          <li>Pick a strong password, don't re-use it on other websites</li>
          <li>Never share your password</li>
          <li>Remember to log out on public computers or devices</li>
          <li>Periodically check your <a href="{{route('settings.security')}}">Account Log</a> for any suspicious activity</li>
          <li><a href="{{route('settings.security.2fa.setup')}}">Setup Two Factor Authentication</a></li>
        </ul>
      </div>
    </div>
  </p>
  <p> 
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#sec-collapse9" role="button" aria-expanded="false" aria-controls="sec-collapse9">
      <i class="fas fa-chevron-down mr-2"></i>
      How can I add additional protection to my account?
    </a>
    <div class="collapse" id="sec-collapse9">
      <div>
        You can add an additional layer of security to your account by enabling <span class="font-weight-bold">Two Factor Authentication</span>. For more information, check your <a href="{{route('settings.security')}}">security settings</a>.
      </div>
    </div>
  </p>
  <p> 
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#sec-collapse10" role="button" aria-expanded="false" aria-controls="sec-collapse10">
      <i class="fas fa-chevron-down mr-2"></i>
      How do I report unauthorized use of my account?
    </a>
    <div class="collapse" id="sec-collapse10">
      <div>
        Please contact the administrators of this instance{{-- , for contact information <a href="{{route('settings')}}">click here</a> --}}.
      </div>
    </div>
  </p>
  <hr>
  <p class="h5 text-muted " id="migration">Migration</p>
  <p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#migrate-collapse1" role="button" aria-expanded="false" aria-controls="migrate-collapse1">
      <i class="fas fa-chevron-down mr-2"></i>
      How can I migrate my account?
    </a>
    <div class="collapse" id="migrate-collapse1">
      <div>
        To migrate your account successfully, your old account must be on a Pixelfed or Mastodon server, or one that supports the Mastodon Account Migration <a href="https://docs.joinmastodon.org/spec/activitypub/#Move">extension</a>.
        <hr>
        <p>Navigate to the <a href="/settings/account/aliases/manage">Account Aliases</a> page in the Settings to begin.</p>
      </div>
    </div>
  </p>
  <p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#migrate-collapse2" role="button" aria-expanded="false" aria-controls="migrate-collapse2">
      <i class="fas fa-chevron-down mr-2"></i>
      How long does the migration take?
    </a>
    <div class="collapse" id="migrate-collapse2">
      <div>
        It can take a few hours to process post migration imports, please contact admins if it takes longer than 24 hours.
      </div>
    </div>
  </p>
  <p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#migrate-collapse3" role="button" aria-expanded="false" aria-controls="migrate-collapse3">
      <i class="fas fa-chevron-down mr-2"></i>
      Why are my posts not migrated?
    </a>
    <div class="collapse" id="migrate-collapse3">
      <div>
        Post migrations are officially supported on Pixelfed servers running v0.11.9+ and higher, and when enabled by server admins.
        <hr>
        It can take a few hours to process post migration imports, please contact admins if it takes longer than 24 hours.
      </div>
    </div>
  </p>
  <hr>
  <p class="h5 text-muted " id="delete-your-account">Delete Your Account</p>
  <p> 
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#del-collapse1" role="button" aria-expanded="false" aria-controls="del-collapse1">
      <i class="fas fa-chevron-down mr-2"></i>
      How do I temporarily disable my account?
    </a>
    <div class="collapse" id="del-collapse1">
      <div>
        <p>If you temporarily disable your account, your profile, photos, comments and likes will be hidden until you reactivate it by logging back in. To temporarily disable your account:</p>
        <ol class="">
          <li>Log into <a href="{{config('app.url')}}">{{config('pixelfed.domain.app')}}</a></li>
          <li>Tap or click the <i class="far fa-user text-dark"></i> menu and select <span class="font-weight-bold text-dark"><i class="fas fa-cog pr-1"></i> Settings</span></li>
          <li>Navigate to the <a href="{{route('settings.security')}}">Security Settings</a></li>
          <li>Confirm your account password.</li>
          <li>Scroll down to the Danger Zone section and click on the <span class="btn btn-sm btn-outline-danger py-1 font-weight-bold">Disable</span> button.</li>
          <li>Follow the instructions on the next page.</li>
        </ol>
      </div>
    </div>
  </p>
  @if(config('pixelfed.account_deletion'))
  <p> 
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#del-collapse2" role="button" aria-expanded="false" aria-controls="del-collapse2">
      <i class="fas fa-chevron-down mr-2"></i>
      How do I delete my account?
    </a>
    <div class="collapse" id="del-collapse2">
      <div>
        @if(config('pixelfed.account_delete_after') == false)
        <div class="bg-light p-3 mb-4">
          <p class="mb-0">When you delete your account, your profile, photos, videos, comments, likes and followers will be <b>permanently removed</b>. If you'd just like to take a break, you can <a href="{{route('settings.remove.temporary')}}">temporarily disable</a> your account instead.</p>
        </div>
        @else
        <div class="bg-light p-3 mb-4">
          <p class="mb-0">When you delete your account, your profile, photos, videos, comments, likes and followers will be <b>permanently removed</b> after {{config('pixelfed.account_delete_after')}} days. You can log in during that period to prevent your account from permanent deletion. If you'd just like to take a break, you can <a href="{{route('settings.remove.temporary')}}">temporarily disable</a> your account instead.</p>
        </div>
        @endif
        <p>After you delete your account, you can't sign up again with the same username on this instance or add that username to another account on this instance, and we can't reactivate deleted accounts.</p>
        <p>To permanently delete your account:</p>
        <ol class="">
          <li>Go to <a href="{{route('settings.remove.permanent')}}">the <span class="font-weight-bold">Delete Your Account</span> page</a>.  If you're not logged into pixelfed on the web, you'll be asked to log in first. You can't delete your account from within a mobile app.</li>
          <li>Navigate to the <a href="{{route('settings.security')}}">Security Settings</a></li>
          <li>Confirm your account password.</li>
          <li>Scroll down to the Danger Zone section and click on the <span class="btn btn-sm btn-outline-danger py-1 font-weight-bold">Delete</span> button.</li>
          <li>Follow the instructions on the next page.</li>
        </ol>
      </div>
    </div>
  </p>
  @endif
@endsection
