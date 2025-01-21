@extends('layouts.app')

@section('content')
<div class="container d-flex justify-content-center">
  <div class="error-page py-5 my-5" style="max-width: 450px;">
    <h3 class="font-weight-bold">Group Unavailable</h3>
    <p class="lead">The group you are trying to view is unavailable</p>

    <div class="text-muted">
		<p class="pt-4 mb-1">This can happen for a few reasons:</p>
		<ul>
			<li>The group url is invalid or has a typo</li>
			<li>We are experiencing higher than usual traffic to this group and have temporarily limited access to this group</li>
			<li>The group has been flagged for review by our automated abuse detection systems</li>
			<li>The group is temporarily disabled by group administrators</li>
			<li>The group has been deleted</li>
		</ul>

		<p class="pt-4">
			If you are a group administrator, you can view your <a href="#" class="font-weight-bold">groups settings</a> for more information.
		</p>
    </div>
  </div>
</div>
@endsection
