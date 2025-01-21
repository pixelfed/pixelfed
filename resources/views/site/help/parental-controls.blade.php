@extends('site.help.partial.template', ['breadcrumb'=>'Parental Controls'])

@section('section')
  <div class="title">
    <h3 class="font-weight-bold">Parental Controls</h3>
  </div>
  <hr>

  <p>In the digital age, ensuring your children's online safety is paramount. Designed with both fun and safety in mind, this feature allows parents to create child accounts, tailor-made for a worry-free social media experience.</p>

  <p class="font-weight-bold text-center">Key Features:</p>

  <ul>
    <li><strong>Child Account Creation</strong>: Easily set up a child account with just a few clicks. This account is linked to your own, giving you complete oversight.</li>
    <li><strong>Post Control</strong>: Decide if your child can post content. This allows you to ensure they're only sharing what's appropriate and safe.</li>
    <li><strong>Comment Management</strong>: Control whether your child can comment on posts. This helps in safeguarding them from unwanted interactions and maintaining a positive online environment.</li>
    <li><strong>Like & Share Restrictions</strong>: You have the power to enable or disable the ability to like and share posts. This feature helps in controlling the extent of your child's social media engagement.</li>
    <li><strong>Disable Federation</strong>: For added safety, you can choose to disable federation for your child's account, limiting their interaction to a more controlled environment.</li>
  </ul>
  <hr>

  <x-collapse title="How do I create a child account?">
    <div>
      @if(config('instance.parental_controls.enabled'))
      <ol>
        <li>Click <a href="/settings/parental-controls">here</a> and tap on the <strong>Add Child</strong> button in the bottom left corner</li>
        <li>Select the Allowed Actions, Enabled features and Preferences</li>
        <li>Enter your childs email address</li>
        <li>Press the <strong>Add Child</strong> buttton</li>
        <li>Open your childs email and tap on the <strong>Accept Invite</strong> button in the email, ensure your parent username is present in the email</li>
        <li>Fill out the child display name, username and password</li>
        <li>Press <strong>Register</strong> and your child account will be active!</li>
      </ol>
      @else
      <p>This feature has been disabled by server admins.</p>
      @endif
    </div>
  </x-collapse>

@if(config('instance.parental_controls.enabled'))
  <x-collapse title="How many child accounts can I create/manage?">
    <div>
      You can create and manage up to <strong>{{ config('instance.parental_controls.limits.max_children') }}</strong> child accounts.
    </div>
  </x-collapse>
@endif
@endsection
