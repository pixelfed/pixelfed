@extends('site.help.partial.template', ['breadcrumb'=>'Your Profile'])

@section('section')


  <div class="title">
    <h3 class="font-weight-bold">Your Profile</h3>
  </div>
  <hr>
  <p class="h5 text-muted font-weight-light">Edit</p>
  <p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse1" role="button" aria-expanded="false" aria-controls="collapse1">
      <i class="fas fa-chevron-down mr-2"></i>
      How do I edit my bio, name, email, or password?
    </a>
    <div class="collapse" id="collapse1">
      <div>
        To create an account using a web browser:
        <ol>
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
        Pixelfed is a federated application, changing your username is not supported in every <a href="">federated software</a> so we cannot allow username changes. Your best option is to create a new account with your desired username.
      </div>
    </div>
  </p>
  <hr>
  <p class="h5 text-muted font-weight-light">Privacy</p>
  <p> 
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse3" role="button" aria-expanded="false" aria-controls="collapse3">
      <i class="fas fa-chevron-down mr-2"></i>
      How do I set my photos and videos to private so that only approved followers can see them?
    </a>
    <div class="collapse" id="collapse3">
      <div>
        To change your account visibility:
        <ol>
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
  <p class="h5 text-muted font-weight-light">Security</p>
  <p> 
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#sec-collapse8" role="button" aria-expanded="false" aria-controls="sec-collapse8">
      <i class="fas fa-chevron-down mr-2"></i>
      How can I secure my account?
    </a>
    <div class="collapse" id="sec-collapse8">
      <div>
        Here are some recommendations to keep your account secure:
        <ul class="font-weight-bold">
          <li>Pick a strong password, don't re-use it on other websites</li>
          <li>Never share your password</li>
          <li>Remember to log out on public computers or devices</li>
          <li>Periodically check your <a href="{{route('settings.security')}}">Account Log</a> for any suspcious activity</li>
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
@endsection