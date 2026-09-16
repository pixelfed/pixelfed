<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config_cache('app.name') }} - Authorization</title>
    <link href="{{ mix('/css/app.css') }}" rel="stylesheet">
    <style>
        .passport-authorize {
            background-color: #f5f6f8;
            min-height: 100vh;
        }

        .passport-authorize .authorize-wrap {
            max-width: 460px;
            margin: 0 auto;
            padding: 40px 16px 32px;
        }

        .passport-authorize .authorize-card {
            border-radius: 14px;
        }

        .passport-authorize .authorize-card .card-body {
            padding: 2rem;
        }

        /* App -> account handoff */
        .passport-authorize .handoff {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
        }

        .passport-authorize .handoff-tile {
            width: 64px;
            height: 64px;
            flex: 0 0 64px;
        }

        .passport-authorize .handoff-app {
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background-color: #343a40;
            color: #fff;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .passport-authorize .handoff-app-official {
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background-color: #fff;
            color: #fff;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .passport-authorize .handoff-avatar {
            border-radius: 50%;
            object-fit: cover;
            background-color: #fff;
            border: 1px solid #e9ecef;
        }

        .passport-authorize .handoff-link {
            position: relative;
            width: 94px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #adb5bd;
        }

        .passport-authorize .handoff-link::before {
            content: '';
            position: absolute;
            left: 6px;
            right: 6px;
            top: 50%;
            border-top: 2px dashed #dee2e6;
        }

        .passport-authorize .handoff-link i {
            position: relative;
            padding: 0 6px;
            background-color: #fff;
            font-size: 0.875rem;
        }

        /* Scope list */
        .passport-authorize .scope-list {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }

        .passport-authorize .scope-item {
            display: flex;
            align-items: center;
            padding: 12px 14px;
            border-top: 1px solid #e9ecef;
        }

        .passport-authorize .scope-list>.scope-item:first-child {
            border-top: 0;
        }

        .passport-authorize .scope-icon {
            flex: 0 0 38px;
            width: 38px;
            height: 38px;
            margin-right: 12px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            background-color: #eef1f4;
            color: #495057;
        }

        .passport-authorize .scope-icon-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .passport-authorize .scope-icon-danger {
            background-color: #f8d7da;
            color: #842029;
        }

        .passport-authorize .scope-body {
            flex: 1 1 auto;
            min-width: 0;
        }

        .passport-authorize .scope-desc {
            margin: 0;
            font-size: 0.9375rem;
            line-height: 1.3;
        }

        .passport-authorize .scope-id {
            display: block;
            margin-top: 2px;
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.75rem;
            color: #868e96;
        }

        /* Expander for extra scopes (no JS needed) */
        .passport-authorize .scope-more>summary {
            list-style: none;
            cursor: pointer;
            user-select: none;
            padding: 10px 14px;
            border-top: 1px solid #e9ecef;
            text-align: center;
            font-size: 0.875rem;
            font-weight: 600;
            color: #007bff;
        }

        .passport-authorize .scope-more>summary::-webkit-details-marker {
            display: none;
        }

        .passport-authorize .scope-more>summary:hover {
            background-color: #f8f9fa;
        }

        .passport-authorize .scope-more>summary:focus-visible {
            outline: 2px solid #007bff;
            outline-offset: -2px;
        }

        .passport-authorize .scope-more>summary .fa-chevron-down {
            margin-left: 6px;
            font-size: 0.75rem;
            transition: transform 0.15s ease;
        }

        .passport-authorize .scope-more[open]>summary .fa-chevron-down {
            transform: rotate(180deg);
        }

        .passport-authorize .scope-more:not([open]) .when-open,
        .passport-authorize .scope-more[open] .when-closed {
            display: none;
        }

        @media (prefers-reduced-motion: reduce) {
            .passport-authorize .scope-more>summary .fa-chevron-down {
                transition: none;
            }
        }

        /* Actions */
        .passport-authorize form {
            margin: 0;
        }

        .passport-authorize .btn-block {
            padding-top: 0.6rem;
            padding-bottom: 0.6rem;
        }
    </style>
</head>

<body class="passport-authorize">
    @php
    $user = request()->user();

    $scopeIcons = [
    'read' => 'fas fa-eye',
    'write' => 'fas fa-pen',
    'follow' => 'fas fa-user-plus',
    'push' => 'fas fa-bell',
    'admin:read' => 'fas fa-server',
    'admin:read:domain_blocks' => 'fas fa-globe',
    'admin:write' => 'fas fa-tools',
    'admin:write:domain_blocks' => 'fas fa-gavel',
    'security:read' => 'fas fa-shield-alt',
    'security:write' => 'fas fa-key',
    ];

    $requestedScopes = collect($scopes);
    $grantableScopes = $user->is_admin
    ? $requestedScopes
    : $requestedScopes->reject(fn ($scope) => \App\Passport\ScopeRepository::isAdminScope($scope->id));
    $droppedAdminScopes = $requestedScopes->count() - $grantableScopes->count();

    $scopeLevel = function ($id) {
    if (\App\Passport\ScopeRepository::isAdminScope($id)) {
    return 'danger';
    }
    if (in_array($id, ['write', 'security:write'])) {
    return 'warning';
    }
    return 'default';
    };

    $levelRank = ['danger' => 0, 'warning' => 1, 'default' => 2];
    $sortedScopes = $grantableScopes
    ->sortBy(fn ($scope) => $levelRank[$scopeLevel($scope->id)])
    ->values();

    $visibleCount = 3;
    $hiddenCount = max($sortedScopes->count() - $visibleCount, 0);
    $moreLabel = 'Show ' . $hiddenCount . ' more ' . \Illuminate\Support\Str::plural('permission', $hiddenCount);
    @endphp

    <div class="authorize-wrap">
        <div class="card authorize-card shadow-sm border-0">
            <div class="card-body">
                <div class="handoff" aria-hidden="true">
                    @if(in_array($client->name, ['Pixelfed for iOS', 'Pixelfed for Android']))
                    <img class="handoff-tile handoff-app-official" src=" /img/pixelfed-icon-color.svg" width="48" height="48" alt="{{ config_cache('app.name') }}">
                    @else
                    <div class="handoff-tile handoff-app">{{ mb_strtoupper(mb_substr($client->name, 0, 1)) }}</div>
                    @endif
                    <div class="handoff-link"><i class="fas fa-link"></i></div>
                    <img class="handoff-tile handoff-avatar" src="{{ $user->profile->avatarUrl() }}" alt="">
                </div>

                <h1 class="h4 font-weight-bold text-center mb-2">Authorize {{ $client->name }}</h1>
                <p class="text-muted text-center mb-4">
                    {{ $client->name }} wants to access your
                    <strong>{{ '@' . $user->username }}</strong> account.
                </p>

                @if ($sortedScopes->count() > 0)
                <p class="small text-muted mb-2">This app will be able to:</p>

                <div class="scope-list">
                    @foreach ($sortedScopes as $scope)
                    @if ($loop->index === $visibleCount)
                    <details class="scope-more">
                        <summary>
                            <span class="when-closed">{{ $moreLabel }}</span>
                            <span class="when-open">Show fewer permissions</span>
                            <i class="fas fa-chevron-down"></i>
                        </summary>
                        @endif

                        @php $level = $scopeLevel($scope->id); @endphp
                        <div class="scope-item">
                            <div class="scope-icon {{ $level !== 'default' ? 'scope-icon-' . $level : '' }}">
                                <i class="{{ $scopeIcons[$scope->id] ?? 'fas fa-unlock' }}"></i>
                            </div>
                            <div class="scope-body">
                                <p class="scope-desc">{{ $scope->description }}</p>
                                <span class="scope-id">{{ $scope->id }}</span>
                            </div>
                        </div>
                        @endforeach

                        @if ($hiddenCount > 0)
                    </details>
                    @endif
                </div>
                @endif

                @if ($droppedAdminScopes > 0)
                <p class="small text-muted mt-2 mb-0">
                    This app also asked for admin permissions, which don't apply to your account.
                </p>
                @endif

                <div class="row no-gutters mt-4">
                    <div class="col-6 pr-2">
                        <form method="post" action="{{ route('passport.authorizations.deny') }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="state" value="{{ $request->state }}">
                            <input type="hidden" name="client_id" value="{{ $client->id }}">
                            <input type="hidden" name="auth_token" value="{{ $authToken }}">
                            <button type="submit" class="btn btn-outline-secondary btn-block font-weight-bold">Cancel</button>
                        </form>
                    </div>
                    <div class="col-6 pl-2">
                        <form method="post" action="{{ route('passport.authorizations.approve') }}">
                            @csrf
                            <input type="hidden" name="state" value="{{ $request->state }}">
                            <input type="hidden" name="client_id" value="{{ $client->id }}">
                            <input type="hidden" name="auth_token" value="{{ $authToken }}">
                            <button type="submit" class="btn btn-primary btn-block font-weight-bold">Authorize</button>
                        </form>
                    </div>
                </div>

                <p class="small text-muted text-center mt-3 mb-0">
                    You can revoke this access at any time from your account settings.
                </p>
            </div>
        </div>

        <p class="small text-muted text-center mt-4 mb-0">
            Signed in as <strong>{{ '@' . $user->username }}</strong>.
            Not you?
            <a href="{{ route('logout') }}" class="font-weight-bold" onclick="event.preventDefault();document.getElementById('logout_auth').submit();">Log out</a>
        </p>

        <form id="logout_auth" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
    </div>
</body>

</html>