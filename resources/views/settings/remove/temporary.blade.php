@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Temporarily Disable Your Account</h3>
  </div>
  <hr>
  <div class="mt-3">
  	<p>Hi <span class="font-weight-bold">{{Auth::user()->username}}</span>,</p>

  	<p>You can disable your account instead of deleting it. This means your account will be hidden until you reactivate it by logging back in.</p>

  	<p class="pb-1">You can only disable your account once a week.</p>

  	<p class="font-weight-bold">Keeping Your Data Safe</p>
  	<p class="pb-3">Nothing is more important to us than the safety and security of this community. People put their trust in us by sharing moments of their lives on Pixelfed. So we will never make any compromises when it comes to safeguarding your data.</p>

  	<p class="pb-2">When you press the button below, your photos, comments and likes will be hidden until you reactivate your account by logging back in.</p>

  	<p>
  		<form method="post">
        @csrf
  		  <button type="submit" class="btn btn-primary font-weight-bold py-0">Temporarily Disable Account</button>
  		</form>
  	</p>
  </div>


@endsection