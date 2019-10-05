<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }} - Authorization</title>

    <!-- Styles -->
    <link href="{{ mix('/css/app.css') }}" rel="stylesheet">

    <style>
        .passport-authorize .container {
            margin-top: 30px;
        }

        .passport-authorize .scopes {
            margin-top: 20px;
        }

        .passport-authorize .buttons {
            margin-top: 25px;
            text-align: center;
        }

        .passport-authorize .btn {
            width: 125px;
        }

        .passport-authorize .btn-approve {
            margin-right: 15px;
        }

        .passport-authorize form {
            display: inline;
        }
    </style>
</head>
<body class="passport-authorize">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="text-center mb-5">
                    <img src="/img/pixelfed-icon-grey.svg">
                </div>
                <div class="card card-default shadow-none border">
                    <div class="card-header text-center font-weight-bold bg-white">
                        Authorization Request
                    </div>
                    <div class="card-body">
                        <!-- Introduction -->
                        <p><strong>{{ $client->name }}</strong> is requesting permission to access your <strong>{{request()->user()->username}}</strong> account.</p>

                        <!-- Scope List -->
                        @if (count($scopes) > 0)
                            <div class="scopes">
                                    <p><strong>This application will be able to:</strong></p>

                                    <ul>
                                        @foreach ($scopes as $scope)
                                            <li><b class="pr-3">{{$scope->id}}</b> {{ $scope->description }}</li>
                                        @endforeach
                                    </ul>
                            </div>
                        @endif

                        <div class="buttons">
                            <!-- Authorize Button -->
                            <form method="post" action="{{ route('passport.authorizations.approve') }}">
                                {{ csrf_field() }}

                                <input type="hidden" name="state" value="{{ $request->state }}">
                                <input type="hidden" name="client_id" value="{{ $client->id }}">
                                <button type="submit" class="btn btn-success font-weight-bold btn-approve">Authorize</button>
                            </form>

                            <!-- Cancel Button -->
                            <form method="post" action="{{ route('passport.authorizations.deny') }}">
                                {{ csrf_field() }}
                                {{ method_field('DELETE') }}

                                <input type="hidden" name="state" value="{{ $request->state }}">
                                <input type="hidden" name="client_id" value="{{ $client->id }}">
                                <button class="btn btn-outline-danger font-weight-bold">Cancel</button>
                            </form>
                        </div>
                        <hr>
                        <p class="mb-0 text-center small text-muted">Click <a href="{{ route('logout') }}" class="font-weight-bold" onclick="event.preventDefault();document.getElementById('logout_auth').submit();">here</a> to log out of this account.</p>
                        <form id="logout_auth" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
