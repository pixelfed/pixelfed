@extends('settings.template')

@section('section')

<div class="d-flex justify-content-between align-items-center">
    <div class="title d-flex align-items-center" style="gap: 1rem;">
        <p class="mb-0"><a href="/settings/home"><i class="far fa-chevron-left fa-lg"></i></a></p>
        <h3 class="font-weight-bold mb-0">Email Settings</h3>
    </div>
</div>

<hr>
<form method="post" action="{{route('settings.email')}}">
    @csrf
    <input type="hidden" class="form-control" name="name" value="{{Auth::user()->profile->name}}">
    <input type="hidden" class="form-control" name="username" value="{{Auth::user()->profile->username}}">
    <input type="hidden" class="form-control" name="website" value="{{Auth::user()->profile->website}}">

    <div class="form-group">
        <label for="email" class="font-weight-bold">Email Address</label>
        <input type="email" class="form-control" id="email" name="email" placeholder="Email Address" value="{{Auth::user()->email}}">
        <p class="help-text small text-muted font-weight-bold">
            @if(Auth::user()->email_verified_at)
            <span class="text-success">Verified</span> {{Auth::user()->email_verified_at->diffForHumans()}}
            @else
            <span class="text-danger">Unverified</span> You need to <a href="/i/verify-email">verify your email</a>.
            @endif
        </p>
    </div>
    <div class="form-group row">
        <div class="col-12 text-right">
            <button type="submit" class="btn btn-primary font-weight-bold py-0 px-5">Submit</button>
        </div>
    </div>
</form>
@endsection
