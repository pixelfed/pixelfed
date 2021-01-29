@extends('site.help.partial.template', ['breadcrumb'=>'Instance Actor'])

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Instance Actor</h3>
  </div>
  <hr>
  <p class="lead">We use a special account type known as an Instance Actor to fetch content securely with other servers in the fediverse.</p>
  <div class="py-4">
    <p class="font-weight-bold h5 pb-3">For Instance Admins</p>
    <p class="mb-0">If you are an instance admin that found this URL in a request or profile, this account is used to fetch content from remote instances using signed requests (HTTP Signatures) to enforce domain block compatibility with other instances.</p>
  </div>
  <hr>
  <div class="card bg-primary border-primary" style="box-shadow: none !important;border: 3px solid #08d!important;">
    <div class="card-header text-light font-weight-bold h4 p-4 bg-primary">Instance Actor Tips</div>
    <div class="card-body bg-white p-3">
      <ul class="pt-3">
        <li class="lead  mb-4">The Instance Actor will not appear in search results.</li>
        <li class="lead  mb-4">You cannot follow an Instance Actor.</li>
        <li class="lead  mb-4">The Instance Actor does not follow accounts.</li>
        <li class="lead">The Instance Actor account does not post or share content from users.</li>
      </ul>
    </div>
  </div>
@endsection