@extends('layouts.app')

@section('content')
@if (session('status'))
    <div class="alert alert-primary px-3 h6 font-weight-bold text-center">
        {{ session('status') }}
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger px-3 h6 text-center">
            @foreach($errors->all() as $error)
                <p class="font-weight-bold mb-1">{{ $error }}</p>
            @endforeach
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger px-3 h6 text-center">
        {{ session('error') }}
    </div>
@endif

<div class="container">
  <div class="col-12">
    <div class="card shadow-none border mt-5">
      <div class="card-body">
        <div class="row">
          <div class="col-12 p-3 p-md-5">
            <div class="title">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="font-weight-bold">Manage Aliases</h3>

                    <a class="font-weight-bold" href="/settings/home">
                        <i class="far fa-long-arrow-left"></i>
                        Back to Settings
                    </a>
                </div>

                <hr />

                <div class="row">
                    <div class="col-12 col-md-7">
                        <p class="lead">If you want to move from another account to this one, you can create an alias here first.</p>
                        <p>This alias is needed before you can move your followers from the old account to this one. Don't worry, making this change is safe and can be undone. The process of moving the account starts from the old one.</p>

                        <p class="mb-0">Your followers will be migrated to your new account, and in some instances your posts too! For more information on Aliases and Account Migration, visit the <a href="/site/kb/your-profile">Help Center</a>.</p>
                    </div>

                    <div class="col-12 col-md-5">
                        <div class="d-flex h-100 justify-content-center align-items-center flex-column">
                            <div class="border rounded-pill px-4 py-2">
                                <p class="small mb-n1 text-lighter font-weight-bold">Old Account</p>
                                <p class="lead mb-0">oldUsername@example.org</p>
                            </div>

                            <div class="border rounded-pill px-4 py-2 mt-3">
                                <p class="small mb-n1 text-lighter font-weight-bold">Old Account</p>
                                <p class="lead mb-0">oldUsername2@example.net</p>
                            </div>

                            <hr>
                            <p class="mb-0 small">We support migration to and from Pixelfed, Mastodon and most other platforms that use the Mastodon Account Migration <a href="https://docs.joinmastodon.org/spec/activitypub/#Move">extension</a>.</p>
                        </div>

                    </div>
                </div>
            </div>
          </div>

          <div class="col-12">
            <hr>
          </div>
          <div class="col-12 col-md-7 p-3 p-md-5">
            <form method="post">
                @csrf

                <div class="form-group">
                    <label class="font-weight-bold mb-0">Old Account</label>
                    <p class="small text-muted">Enter the username@domain of your old account</p>
                    <input type="email" class="form-control" name="acct" placeholder="username@domain.tld"/>
                </div>

                <button class="btn btn-primary btn-block font-weight-bold btn-lg">Create Alias</button>
            </form>
          </div>

          <div class="col-12 col-md-5 p-3 p-md-5 bg-white">
            <p class="text-center font-weight-bold">Aliases</p>
            <div class="list-group">
                @if(count($aliases))
                @foreach($aliases as $alias)
                <div class="list-group-item d-flex justify-content-between">
                    <div class="mb-0 font-weight-bold small text-break">
                        {{ $alias->acct }}
                    </div>

                    <div class="ml-2 mb-0 font-weight-bold small">
                        <form action="/settings/account/aliases/manage/delete" method="post">
                            @csrf
                            <input type="hidden" name="id" value="{{ $alias->id }}">
                            <input type="hidden" name="acct" value="{{ $alias->acct }}">
                            <button class="btn btn-link btn-sm p-0">
                                <i class="far fa-trash-alt text-danger"></i>
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
                @else
                <div class="border rounded p-5">
                    <p class="text-center mb-0">No aliases found!</p>
                </div>
                @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
