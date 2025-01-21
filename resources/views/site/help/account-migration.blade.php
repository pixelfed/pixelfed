@extends('site.help.partial.template', ['breadcrumb'=>'Account Migration'])

@section('section')

    <div class="title">
        <h3 class="font-weight-bold">Account Migration</h3>
    </div>

    <hr>

    @if((bool) config_cache('federation.migration') === false)
    <div class="alert alert-danger">
        <p class="font-weight-bold mb-0">Account Migration is not available on this server.</p>
    </div>
    @endif

    <p class="lead">Account Migration is a feature that allows users to move their account followers from one Pixelfed instance (server) to another.</p>
    <p class="lead">This can be useful if a user wants to switch to a different instance due to preferences for its community, policies, or features.</p>
@endsection
