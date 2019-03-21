@extends('site.help.partial.template', ['breadcrumb'=>__('helpcenter.gettingStarted')])

@section('section')

<div class="title">
	<h3 class="font-weight-bold">{{__('helpcenter.gettingStarted')}}</h3>
</div>
<hr>
<p class="lead ">Welcome to Pixelfed!</p>
<hr>
<p>
	<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse1" role="button" aria-expanded="false" aria-controls="collapse1">
		<i class="fas fa-chevron-down mr-2"></i>
		How do I create a Pixelfed account?
	</a>
	<div class="collapse" id="collapse1">
		<div>
			To create an account using a web browser:
			<ol>
				<li>Go to <a href="{{config('app.url')}}">{{config('app.url')}}</a>.</li>
				<li>Click on the register link at the top of the page.</li>
				<li>Enter your name, email address, username and password.</li>
				@if(config('pixelfed.enforce_email_verification') != true)
				<li>Wait for an account verification email, it may take a few minutes.</li>
				@endif
			</ol>
		</div>
	</div>
</p>
<p>	
	<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse2" role="button" aria-expanded="false" aria-controls="collapse2">
		<i class="fas fa-chevron-down mr-2"></i>
		How to I update profile info like name, bio, email?
	</a>
	<div class="collapse" id="collapse2">
		<div>
			You can update your account by visiting the <a href="{{route('settings')}}">account settings</a> page.
		</div>
	</div>
</p>
<p>	
	<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse3" role="button" aria-expanded="false" aria-controls="collapse3">
		<i class="fas fa-chevron-down mr-2"></i>
		What can I do if a username I want is taken but seems inactive?
	</a>
	<div class="collapse" id="collapse3">
		<div class="mt-2">
			If your desired username is taken you can add underscores, dashes, or numbers to make it unique.
		</div>
	</div>
</p>
<p>	
	<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse4" role="button" aria-expanded="false" aria-controls="collapse4">
		<i class="fas fa-chevron-down mr-2"></i>
		Why can't I change my username?
	</a>
	<div class="collapse" id="collapse4">
		<div class="mt-2">
			Pixelfed is a federated application, changing your username is not supported in every <a href="https://en.wikipedia.org/wiki/ActivityPub">federated software</a> so we cannot allow username changes. Your best option is to create a new account with your desired username. 
		</div>
	</div>
</p>
<p>	
	<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse5" role="button" aria-expanded="false" aria-controls="collapse5">
		<i class="fas fa-chevron-down mr-2"></i>
		I received an email that I created an account, but I never signed up for one.
	</a>
	<div class="collapse" id="collapse5">
		<div class="mt-2">
			Someone may have registered your email by mistake. If you would like your email to be removed from the account please contact an admin of this instance.
		</div>
	</div>
</p>
<p>	
	<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse6" role="button" aria-expanded="false" aria-controls="collapse6">
		<i class="fas fa-chevron-down mr-2"></i>
		I can't create a new account because an account with this email already exists.
	</a>
	<div class="collapse" id="collapse6">
		<div class="mt-2">
			You might have registered before, or someone may have used your email by mistake. Please contact an admin of this instance.
		</div>
	</div>
</p>

@endsection