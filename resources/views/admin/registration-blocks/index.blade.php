@extends('admin.partial.template-full')

@section('section')
</div>
<div class="header bg-primary pb-3 mt-n4">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-8 col-12">
                    <p class="display-1 text-white d-inline-block mb-0">Registration Blocks</p>
                    <p class="text-white mb-0">Stop new accounts by IP or range, email address, email domain or username. Existing accounts are not affected.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid mt-4">
    <div class="row">
        @if (session('status'))
        <div class="col-12" id="flash">
            <div class="alert alert-success font-weight-bold">{{ session('status') }}</div>
        </div>
        @endif

        @if ($errors->any())
        <div class="col-12">
            <div class="alert alert-danger">
                <ul class="mb-0 pl-3">
                    @foreach ($errors->all() as $error)
                    <li class="font-weight-bold">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        <div class="col-12 col-xl-8 mb-4">
            <ul class="nav nav-pills mb-3">
                @foreach (['all' => 'All', 'ip' => 'IP', 'email' => 'Email', 'email_domain' => 'Email domain', 'username' => 'Username'] as $key => $label)
                <li class="nav-item">
                    <a class="nav-link {{ $type === $key ? 'active' : '' }}" href="?{{ http_build_query(['type' => $key, 'status' => $status, 'q' => $q]) }}">
                        {{ $label }}
                        @if ($key !== 'all' && isset($counts[$key]))
                        <span class="badge badge-light ml-1">{{ $counts[$key] }}</span>
                        @endif
                    </a>
                </li>
                @endforeach
            </ul>

            <form method="get" class="form-inline mb-3">
                <input type="hidden" name="type" value="{{ $type }}">
                <select name="status" class="custom-select custom-select-sm mr-2 mb-2" onchange="this.form.submit()">
                    @foreach (['active' => 'Active', 'expired' => 'Expired', 'disabled' => 'Disabled', 'all' => 'All statuses'] as $key => $label)
                    <option value="{{ $key }}" {{ $status === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm mr-2 mb-2" placeholder="Search patterns and notes">
                <button type="submit" class="btn btn-sm btn-outline-primary mb-2">Search</button>
            </form>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-flush mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th scope="col">Pattern</th>
                                <th scope="col">Type</th>
                                <th scope="col">Hits</th>
                                <th scope="col">Expires</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($blocks as $block)
                            <tr>
                                <td style="max-width: 320px;">
                                    <code class="text-dark text-break">{{ $block->pattern }}</code>
                                    @if ($block->note)
                                    <div class="small text-muted text-wrap">{{ $block->note }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="font-weight-bold">{{ str_replace('_', ' ', $block->type) }}</span>
                                    @if ($block->type !== 'ip')
                                    <div class="small text-muted">{{ $block->match_type }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="font-weight-bold">{{ number_format($block->hits) }}</span>
                                    @if ($block->last_hit_at)
                                    <div class="small text-muted">{{ $block->last_hit_at->diffForHumans() }}</div>
                                    @endif
                                </td>
                                <td class="small">
                                    {{ $block->expires_at ? $block->expires_at->diffForHumans() : 'Never' }}
                                </td>
                                <td>
                                    @php($st = $block->status())
                                    <span class="badge {{ $st === 'active' ? 'badge-success' : ($st === 'expired' ? 'badge-warning' : 'badge-secondary') }}">{{ $st }}</span>
                                </td>
                                <td class="text-right text-nowrap">
                                    <form method="post" action="/i/admin/registration-blocks/{{ $block->id }}/toggle" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">{{ $block->is_active ? 'Disable' : 'Enable' }}</button>
                                    </form>
                                    <form method="post" action="/i/admin/registration-blocks/{{ $block->id }}" class="d-inline" onsubmit="return window.confirm('Delete this block?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    No registration blocks here yet. Add one with the form on this page.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-3">{{ $blocks->links() }}</div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card mb-4">
                <div class="card-header font-weight-bold">Add blocks</div>
                <div class="card-body">
                    <form method="post" action="/i/admin/registration-blocks">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-6">
                                <label class="small font-weight-bold" for="rb-type">Block by</label>
                                <select class="custom-select" id="rb-type" name="type">
                                    <option value="ip" {{ old('type', $type) === 'ip' ? 'selected' : '' }}>IP or range</option>
                                    <option value="email" {{ old('type', $type) === 'email' ? 'selected' : '' }}>Email address</option>
                                    <option value="email_domain" {{ old('type', $type) === 'email_domain' ? 'selected' : '' }}>Email domain</option>
                                    <option value="username" {{ old('type', $type) === 'username' ? 'selected' : '' }}>Username</option>
                                </select>
                            </div>
                            <div class="form-group col-6" id="rb-match-group">
                                <label class="small font-weight-bold" for="rb-match">Match</label>
                                <select class="custom-select" id="rb-match" name="match_type">
                                    <option value="exact" {{ old('match_type') === 'exact' ? 'selected' : '' }}>Exact</option>
                                    <option value="wildcard" {{ old('match_type', 'wildcard') === 'wildcard' ? 'selected' : '' }}>Wildcard</option>
                                    <option value="regex" {{ old('match_type') === 'regex' ? 'selected' : '' }}>Regex</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="small font-weight-bold" for="rb-patterns">Patterns, one per line</label>
                            <textarea class="form-control text-monospace" id="rb-patterns" name="patterns" rows="5" style="font-size: 13px;">{{ old('patterns') }}</textarea>
                            <small class="form-text text-muted" id="rb-help"></small>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-7">
                                <label class="small font-weight-bold" for="rb-note">Note</label>
                                <input type="text" class="form-control" id="rb-note" name="note" maxlength="490" value="{{ old('note') }}" placeholder="Only admins see this">
                            </div>
                            <div class="form-group col-5">
                                <label class="small font-weight-bold" for="rb-expires">Expires</label>
                                <select class="custom-select" id="rb-expires" name="expires">
                                    @foreach ($expiryOptions as $key => $label)
                                    <option value="{{ $key }}" {{ old('expires', 'never') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block font-weight-bold">Add blocks</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header font-weight-bold">Test a signup</div>
                <div class="card-body">
                    @if (session('test_result'))
                        @php($tr = session('test_result'))
                        @if ($tr['blocked'])
                        <div class="alert alert-danger small">
                            <strong>Blocked</strong> by {{ str_replace('_', ' ', $tr['rule']['type']) }} rule
                            <code class="text-white">{{ $tr['rule']['pattern'] }}</code>
                            @if ($tr['rule']['type'] !== 'ip')({{ $tr['rule']['match_type'] }})@endif
                        </div>
                        @else
                        <div class="alert alert-success small"><strong>Allowed.</strong> No active block matches.</div>
                        @endif
                    @endif
                    <form method="post" action="/i/admin/registration-blocks/test">
                        @csrf
                        <div class="form-group">
                            <label class="small font-weight-bold" for="test-ip">IP address</label>
                            <input type="text" class="form-control" id="test-ip" name="test_ip" value="{{ old('test_ip') }}" placeholder="203.0.113.7">
                        </div>
                        <div class="form-group">
                            <label class="small font-weight-bold" for="test-email">Email</label>
                            <input type="text" class="form-control" id="test-email" name="test_email" value="{{ old('test_email') }}" placeholder="someone@example.com">
                        </div>
                        <div class="form-group">
                            <label class="small font-weight-bold" for="test-username">Username</label>
                            <input type="text" class="form-control" id="test-username" name="test_username" value="{{ old('test_username') }}" placeholder="username">
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-block font-weight-bold">Test</button>
                        <p class="small text-muted mt-2 mb-0">Tests do not count as hits. Run <code>php artisan admin:registration-blocks scan</code> to check existing accounts.</p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
(function () {
    var help = {
        ip: 'Single addresses or CIDR ranges. Examples: 203.0.113.7, 198.51.100.0/24, 2001:db8::/32',
        email: {
            exact: 'Full addresses. Plus tags and Gmail dots are ignored, so spam@gmail.com also blocks s.pam+1@gmail.com',
            wildcard: '* matches anything, ? matches one character. Examples: *casino*@*, promo??@gmail.com',
            regex: 'Case-insensitive, no delimiters. Example: ^[a-z]{6}[0-9]{4}@gmail\\.com$'
        },
        email_domain: {
            exact: 'Domains, subdomains included. Example: spam.example also blocks mail.spam.example',
            wildcard: 'Examples: *.ru, mail*.example',
            regex: 'Matched against the domain only. Example: ^[a-z0-9]+\\.(xyz|top)$'
        },
        username: {
            exact: 'Separators are ignored, so freecoins also blocks free_coins',
            wildcard: 'Examples: *casino*, *crypto*, seo-*',
            regex: 'Example: ^[a-z]+[0-9]{5,}$'
        }
    };
    var type = document.getElementById('rb-type');
    var match = document.getElementById('rb-match');
    var group = document.getElementById('rb-match-group');
    var out = document.getElementById('rb-help');
    function update() {
        var t = type.value;
        group.style.visibility = t === 'ip' ? 'hidden' : 'visible';
        out.textContent = t === 'ip' ? help.ip : help[t][match.value];
    }
    type.addEventListener('change', update);
    match.addEventListener('change', update);
    if (type.value === 'all' || !help[type.value]) { type.value = 'ip'; }
    update();
    setTimeout(function () { var f = document.getElementById('flash'); if (f) { f.remove(); } }, 5000);
})();
</script>
@endpush
