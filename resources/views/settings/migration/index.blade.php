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
                    <h3 class="font-weight-bold">Account Migration</h3>

                    <a class="font-weight-bold" href="/settings/home">
                        <i class="far fa-long-arrow-left"></i>
                        Back to Settings
                    </a>
                </div>
                <hr />
            </div>
            <div>
                @if($hasExistingMigration)
                <div class="row">
                    <div class="col-12 mt-5">
                        <p class="lead text-center">You have migrated your account already.</p>
                        <p>You can only migrate your account once per 30 days. If you want to migrate your followers back to this account, follow this process in reverse.</p>
                    </div>
                </div>
                @else
                <div class="row">
                    <div class="col-12">
                        <p class="lead">If you want to move this account to another account, please read the following carefully.</p>
                        <ul class="text-danger lead">
                            <li class="font-weight-bold">Only followers will be transferred; no other information will be moved automatically.</li>
                            <li>This process will transfer all followers from your existing account to your new account.</li>
                            <li>A redirect notice will be added to your current account's profile, and it will be removed from search results.</li>
                            <li>You must set up the new account to link back to your current account before proceeding.</li>
                            <li>Once the transfer is initiated, there will be a waiting period during which you cannot initiate another transfer.</li>
                            <li>After the transfer, your current account will be limited in functionality, but you will retain the ability to export data and possibly reactivate the account.</li>
                        </ul>
                        <p class="mb-0">For more information on Aliases and Account Migration, visit the <a href="/site/kb/your-profile">Help Center</a>.</p>
                        <hr>

                        <form method="post" autocomplete="off">
                            @csrf
                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold mb-0">New Account Handle</label>
                                        <p class="small text-muted">Enter the username@domain of the account you want to move to</p>
                                        <input
                                            type="email"
                                            class="form-control"
                                            name="acct"
                                            placeholder="username@domain.tld"
                                            role="presentation"
                                            autocomplete="new-user-email"
                                        />
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold mb-0">Account Password</label>
                                        <p class="small text-muted">For security purposes please enter the password of the current account</p>
                                        <input
                                            type="password"
                                            class="form-control"
                                            name="password"
                                            role="presentation"
                                            placeholder="Your account password"
                                            autocomplete="new-password"
                                            />
                                    </div>
                                </div>
                            </div>

                            <button class="btn btn-primary btn-block font-weight-bold btn-lg rounded-pill">Move Followers</button>
                        </form>
                    </div>
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
