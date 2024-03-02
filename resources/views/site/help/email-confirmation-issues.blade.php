@extends('site.help.partial.template', ['breadcrumb'=>'Email Confirmation Issues'])

@section('section')
<div class="title">
    <h3 class="font-weight-bold">Email Confirmation Issues</h3>
</div>
<hr>
<p>If you have been redirected to this page, it may be due to one of the following reasons:</p>

<ul>
    <li>The email confirmation link has already been used.</li>
    <li>The email confirmation link may have expired, they are only valid for 24 hours.</li>
    <li>You cannot confirm an email for another account while logged in to a different account. Try logging out, or use a different browser to open the email confirmation link.</li>
    <li>The account the associated email belongs to may have been deleted, or the account may have changed the email address.</li>
</ul>
@endsection
