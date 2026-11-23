@extends('admin.partial.template-full')

@section('section')
</div>
<div class="header bg-primary pb-3 mt-n4">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-8 col-12">
                    <p class="display-1 text-white d-inline-block mb-0">IP Data</p>
                    <p class="text-white mb-0">Country and network (ASN) lookups for signup IPs. Upload the CSVs once, then see where accounts come from on Users, Moderation and the Dashboard.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid mt-4" id="ip-data" data-chunk-size="{{ $chunkSize }}" data-running="{{ $running ? '1' : '0' }}" data-zip="{{ $zipSupported ? '1' : '0' }}">
    @if (session('status'))
    <div class="alert alert-success font-weight-bold" id="flash">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 pl-3">
            @foreach ($errors->all() as $error)
            <li class="font-weight-bold">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="row">
        @foreach (['country' => ['label' => 'IP to Country', 'icon' => 'fa-globe-americas', 'entity' => 'countries', 'about' => 'Adds the country and continent to signup IPs.'], 'asn' => ['label' => 'IP to ASN', 'icon' => 'fa-server', 'entity' => 'ASNs', 'about' => 'Adds the network, organization and domain, and lets you block a network range or a whole ASN.']] as $type => $meta)
        @php($active = $datasets[$type]['active'])
        @php($runningSet = $datasets[$type]['running'])
        <div class="col-12 col-lg-4 mb-4">
            <div class="card h-100 mb-0">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-uppercase small font-weight-bold text-muted"><i class="fas {{ $meta['icon'] }} mr-2"></i>{{ $meta['label'] }}</span>
                        @if ($runningSet)
                        <span class="badge badge-info">{{ $active ? 'Updating' : 'Importing' }}</span>
                        @elseif ($active)
                        <span class="badge badge-success">Active</span>
                        @else
                        <span class="badge badge-secondary">Not loaded</span>
                        @endif
                    </div>

                    @if ($active)
                    <div class="mb-3">
                        <div class="h1 font-weight-bold text-dark mb-0">{{ number_format($active->rows_imported) }}</div>
                        <div class="small text-muted">networks, {{ number_format($active->entities) }} {{ $meta['entity'] }}</div>
                    </div>
                    <dl class="row small mb-0">
                        <dt class="col-6 text-muted font-weight-normal">IPv4</dt>
                        <dd class="col-6 font-weight-bold text-dark">{{ number_format($active->networks_v4) }}</dd>
                        <dt class="col-6 text-muted font-weight-normal">IPv6</dt>
                        <dd class="col-6 font-weight-bold text-dark">{{ number_format($active->networks_v6) }}</dd>
                        <dt class="col-6 text-muted font-weight-normal">Imported</dt>
                        <dd class="col-6 font-weight-bold text-dark" title="{{ $active->activated_at?->toDateTimeString() }}">{{ $active->activated_at?->diffForHumans() }}@if ($active->creator) by &commat;{{ $active->creator->username }}@endif</dd>
                        <dt class="col-6 text-muted font-weight-normal">Source</dt>
                        <dd class="col-6 font-weight-bold text-dark text-truncate" title="{{ $active->source_name }}">{{ $active->source_name }} ({{ \App\Util\Lexer\PrettyNumber::size($active->source_size) }})</dd>
                    </dl>
                    @else
                    <p class="text-muted small mb-3">{{ $meta['about'] }}</p>
                    @endif

                    @if ($runningSet)
                    <div class="bg-lighter rounded p-3 mt-3" data-dataset="{{ $runningSet->id }}">
                        <div class="d-flex justify-content-between small mb-2">
                            <span class="font-weight-bold text-dark text-truncate mr-2">Importing {{ $runningSet->source_name }}</span>
                            <span class="font-weight-bold text-dark" data-progress-text>{{ $runningSet->progress() }}%</span>
                        </div>
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-primary" role="progressbar" data-progress-bar style="width: {{ $runningSet->progress() }}%;" aria-valuenow="{{ $runningSet->progress() }}" aria-valuemin="0" aria-valuemax="100" aria-label="Import progress"></div>
                        </div>
                        <div class="small text-muted" data-progress-rows>{{ number_format($runningSet->rows_imported) }} networks so far</div>
                        <div class="small mt-1">{{ $active ? 'Lookups keep using the current data until this finishes.' : 'Lookups start as soon as this finishes.' }}</div>
                        <form method="post" action="/i/admin/ip-data/{{ $runningSet->id }}/cancel" class="mt-2" onsubmit="return window.confirm('Cancel this import?')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger">Cancel import</button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach

        <div class="col-12 col-lg-4 mb-4">
            <div class="card h-100 mb-0">
                <div class="card-body d-flex flex-column">
                    <span class="text-uppercase small font-weight-bold text-muted mb-3">Upload</span>
                    <label for="ipd-file" id="ipd-drop" class="d-flex flex-column align-items-center justify-content-center text-center rounded p-4 mb-3 flex-grow-1" style="border: 2px dashed #c3cbe0; cursor: pointer;">
                        <span class="rounded-circle bg-lighter d-inline-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px;">
                            <i class="fas fa-cloud-upload-alt text-primary"></i>
                        </span>
                        <span class="font-weight-bold text-dark">Drop a .csv{{ $zipSupported ? ' or .csv.zip' : '' }}</span>
                        <span class="small text-muted">Country or ASN is detected from the header row.{{ $zipSupported ? ' The zipped files from iplocate.io are much smaller to upload.' : '' }}</span>
                        <span class="btn btn-sm btn-primary mt-2">Choose file</span>
                    </label>
                    <input type="file" id="ipd-file" class="d-none" accept=".csv{{ $zipSupported ? ',.zip' : '' }}">
                    <div id="ipd-progress" class="d-none mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="font-weight-bold text-dark text-truncate mr-2" id="ipd-name"></span>
                            <span class="text-muted" id="ipd-percent"></span>
                        </div>
                        <div class="progress mb-1" style="height: 8px;">
                            <div class="progress-bar bg-primary" id="ipd-bar" role="progressbar" style="width: 0%;" aria-label="Upload progress"></div>
                        </div>
                        <div class="small text-muted" id="ipd-text"></div>
                    </div>
                    <div id="ipd-error" class="alert alert-danger small d-none mb-3" role="alert"></div>
                    <p class="small text-muted mb-1">Get the free databases from <a href="https://iplocate.io/free-databases" rel="noopener">iplocate.io</a>. Or import from the shell:</p>
                    <code class="d-block bg-default text-white small p-2 rounded text-nowrap" style="overflow-x: auto;">php artisan app:ip-data import ip-to-asn.csv.zip</code>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4" id="lookup">
        <div class="card-body">
            <h3 class="mb-0">Look up an IP</h3>
            <p class="small text-muted">Uses the active data. Results are cached per IP and reset when a dataset is replaced.</p>
            <form method="get" action="{{ route('admin.ip-data') }}#lookup" class="form-row align-items-end">
                <input type="hidden" name="range" value="{{ $range }}">
                <div class="col-12 col-md-6 col-lg-4 mb-2">
                    <label class="small font-weight-bold" for="ipd-ip">IP address</label>
                    <input type="text" class="form-control text-monospace" id="ipd-ip" name="ip" value="{{ $ip }}" placeholder="203.0.113.7">
                </div>
                <div class="col-auto mb-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i> Look up</button>
                </div>
            </form>

            @if ($ip !== null)
                @if (! $validIp)
                <div class="alert alert-warning small mb-0 mt-2">That is not a valid IPv4 or IPv6 address.</div>
                @elseif (! $available)
                <div class="alert alert-secondary small mb-0 mt-2">No IP data has been imported yet.</div>
                @elseif (! $lookup)
                <div class="alert alert-secondary small mb-0 mt-2">No match for <span class="text-monospace">{{ $ip }}</span> in the active data.</div>
                @else
                <div class="row mt-3">
                    <div class="col-12 col-md-6 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="text-uppercase small font-weight-bold text-muted mb-1">Country</div>
                            @if ($lookup['country'])
                            <div class="d-flex align-items-center mb-1" style="gap: .5rem;">
                                <span class="badge badge-secondary">{{ $lookup['country']['code'] }}</span>
                                <span class="h4 font-weight-bold mb-0">{{ $lookup['country']['name'] }}</span>
                            </div>
                            @if ($lookup['country']['continent'])
                            <div class="small">Continent {{ $lookup['country']['continent'] }}</div>
                            @endif
                            <div class="small text-muted">Network <span class="text-monospace text-dark">{{ $lookup['country']['network'] }}</span></div>
                            @else
                            <div class="small text-muted">No country match.</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="text-uppercase small font-weight-bold text-muted mb-1">Network</div>
                            @if ($lookup['asn'])
                            <div class="d-flex align-items-baseline flex-wrap mb-1" style="gap: .5rem;">
                                <span class="h4 font-weight-bold text-primary mb-0">AS{{ $lookup['asn']['number'] }}</span>
                                <span class="small text-muted">{{ $lookup['asn']['name'] }}</span>
                            </div>
                            <div class="small"><span class="font-weight-bold text-dark">{{ $lookup['asn']['org'] }}</span>@if ($lookup['asn']['domain']) &middot; {{ $lookup['asn']['domain'] }}@endif</div>
                            <div class="small text-muted">Network <span class="text-monospace text-dark">{{ $lookup['asn']['network'] }}</span></div>
                            @else
                            <div class="small text-muted">No ASN match.</div>
                            @endif
                        </div>
                    </div>
                </div>
                @php($lookupNetwork = $lookup['asn']['network'] ?? $lookup['country']['network'] ?? null)
                <div class="d-flex flex-wrap justify-content-between align-items-center border-top pt-3" style="gap: .5rem;">
                    <div class="small">
                        <i class="fas fa-users text-muted mr-1"></i>
                        <strong class="text-dark">{{ number_format($sameIp) }} local {{ \Illuminate\Support\Str::plural('account', $sameIp) }}</strong> signed up from this IP
                        @if ($sameIp)
                        <a href="{{ route('admin.users', ['a' => 'search', 'q' => 'ip:'.$ip]) }}" class="font-weight-bold ml-1">View</a>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap" style="gap: .5rem;">
                        <a href="{{ route('admin.registration-blocks', ['type' => 'ip', 'status' => 'all', 'pattern' => $ip, 'note' => 'From IP lookup']) }}#add-blocks" class="btn btn-sm btn-outline-danger">Block IP</a>
                        @if ($lookupNetwork && \App\Services\RegistrationBlockService::isValidIpOrCidr($lookupNetwork))
                        <a href="{{ route('admin.registration-blocks', ['type' => 'ip', 'status' => 'all', 'pattern' => $lookupNetwork, 'note' => 'From IP lookup of '.$ip]) }}#add-blocks" class="btn btn-sm btn-outline-danger">Block {{ $lookupNetwork }}</a>
                        @endif
                        @if ($lookup['asn'])
                        <a href="{{ route('admin.registration-blocks', ['type' => 'asn', 'status' => 'all', 'pattern' => 'AS'.$lookup['asn']['number'], 'note' => 'AS'.$lookup['asn']['number'].' '.($lookup['asn']['org'] ?? '')]) }}#add-blocks" class="btn btn-sm btn-outline-danger">Block AS{{ $lookup['asn']['number'] }}</a>
                        @endif
                    </div>
                </div>
                @endif
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-xl-7 mb-4">
            <div class="card h-100 mb-0">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap: .5rem;">
                    <span class="font-weight-bold">Top signup networks</span>
                    <form method="get" action="{{ route('admin.ip-data') }}" class="form-inline">
                        @if ($ip)<input type="hidden" name="ip" value="{{ $ip }}">@endif
                        <label class="small mr-2" for="ipd-range">Range</label>
                        <select class="custom-select custom-select-sm" id="ipd-range" name="range" onchange="this.form.submit()">
                            @foreach ($ranges as $key => $r)
                            <option value="{{ $key }}" {{ $range === $key ? 'selected' : '' }}>{{ $r['label'] }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                @if (count($topNetworks))
                @php($maxNetwork = max(array_column($topNetworks, 'total')))
                <ul class="list-group list-group-flush">
                    @foreach ($topNetworks as $row)
                    <li class="list-group-item">
                        <div class="d-flex flex-wrap align-items-center" style="gap: .5rem 1rem;">
                            <div class="flex-grow-1" style="min-width: 220px;">
                                <span class="font-weight-bold text-primary">AS{{ $row['value'] }}</span>
                                <span class="font-weight-bold text-dark ml-1">{{ $row['label'] ?? 'Unknown' }}</span>
                                <div class="progress mt-2 mb-0" style="height: 6px;">
                                    <div class="progress-bar {{ $row['total'] > 0 && $row['removed'] / $row['total'] > 0.5 ? 'bg-danger' : 'bg-primary' }}" style="width: {{ max(2, round($row['total'] / $maxNetwork * 100)) }}%;"></div>
                                </div>
                            </div>
                            <div class="text-right small" style="width: 130px;">
                                <div class="font-weight-bold text-dark">{{ number_format($row['total']) }} {{ \Illuminate\Support\Str::plural('signup', $row['total']) }}</div>
                                <div class="text-muted">{{ number_format($row['removed']) }} deleted or disabled</div>
                            </div>
                            <div class="d-flex" style="gap: .5rem;">
                                <a href="{{ route('admin.users', ['a' => 'search', 'q' => 'asn:'.$row['value']]) }}" class="btn btn-sm btn-outline-primary">Users</a>
                                <a href="{{ route('admin.registration-blocks', ['type' => 'asn', 'status' => 'all', 'pattern' => 'AS'.$row['value'], 'note' => 'AS'.$row['value'].' '.($row['label'] ?? '')]) }}#add-blocks" class="btn btn-sm btn-outline-danger">Block ASN</a>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
                @else
                <div class="card-body text-muted small">{{ $available ? 'No signups with a known network in this range.' : 'Import IP data to see where signups come from.' }}</div>
                @endif
            </div>
        </div>
        <div class="col-12 col-xl-5 mb-4">
            <div class="card h-100 mb-0">
                <div class="card-header font-weight-bold">Top signup countries</div>
                @if (count($topCountries))
                <ul class="list-group list-group-flush">
                    @foreach ($topCountries as $row)
                    <li class="list-group-item d-flex align-items-center" style="gap: .75rem;">
                        <span class="badge badge-secondary">{{ $row['value'] }}</span>
                        <a href="{{ route('admin.users', ['a' => 'search', 'q' => 'country:'.$row['value']]) }}" class="flex-grow-1 font-weight-bold text-dark">{{ $row['label'] ?? $row['value'] }}</a>
                        <span class="font-weight-bold text-dark">{{ number_format($row['total']) }}</span>
                    </li>
                    @endforeach
                </ul>
                @else
                <div class="card-body text-muted small">{{ $available ? 'No signups with a known country in this range.' : 'Import IP data to see where signups come from.' }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="card mb-4" id="history">
        <div class="card-header font-weight-bold">Import history</div>
        <div class="table-responsive">
            <table class="table table-flush mb-0">
                <thead class="thead-light">
                    <tr>
                        <th scope="col">Dataset</th>
                        <th scope="col">File</th>
                        <th scope="col" class="text-right">Networks</th>
                        <th scope="col">Status</th>
                        <th scope="col">Imported</th>
                        <th scope="col">Took</th>
                        <th scope="col">By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($history as $item)
                    <tr>
                        <td class="font-weight-bold">{{ $item->label() }}</td>
                        <td class="text-monospace small">{{ $item->source_name }}</td>
                        <td class="text-right">{{ number_format($item->rows_imported) }}</td>
                        <td>
                            @php($badge = ['active' => 'badge-success', 'pending' => 'badge-info', 'importing' => 'badge-info', 'failed' => 'badge-danger', 'cancelled' => 'badge-warning'][$item->status] ?? 'badge-secondary')
                            <span class="badge {{ $badge }}">{{ $item->status }}</span>
                            @if ($item->error && in_array($item->status, ['failed', 'cancelled']))
                            <div class="small text-danger text-wrap mt-1" style="max-width: 360px;">{{ $item->error }}</div>
                            @endif
                            @if ($item->rows_skipped > 0)
                            <div class="small text-muted mt-1">{{ number_format($item->rows_skipped) }} rows skipped</div>
                            @endif
                        </td>
                        <td class="small" title="{{ $item->created_at?->toDateTimeString() }}">{{ $item->created_at?->diffForHumans() }}</td>
                        <td class="small">
                            @php($took = $item->duration())
                            {{ $took === null ? '' : ($took >= 60 ? intdiv($took, 60).'m '.str_pad((string) ($took % 60), 2, '0', STR_PAD_LEFT).'s' : $took.'s') }}
                        </td>
                        <td class="small">{{ $item->creator ? '@'.$item->creator->username : 'CLI' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">No imports yet. Upload the IP to Country and IP to ASN files above.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="small text-muted">IP address data powered by <a href="https://iplocate.io" rel="noopener">IPLocate.io</a></p>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
(function () {
    var root = document.getElementById('ip-data');
    if (!root) { return; }

    var chunkSize = parseInt(root.getAttribute('data-chunk-size'), 10) || 1048576;
    var zipSupported = root.getAttribute('data-zip') === '1';
    var input = document.getElementById('ipd-file');
    var drop = document.getElementById('ipd-drop');
    var box = document.getElementById('ipd-progress');
    var nameEl = document.getElementById('ipd-name');
    var percentEl = document.getElementById('ipd-percent');
    var barEl = document.getElementById('ipd-bar');
    var textEl = document.getElementById('ipd-text');
    var errorEl = document.getElementById('ipd-error');
    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var busy = false;

    function formatSize(bytes) {
        if (bytes >= 1048576) { return (bytes / 1048576).toFixed(1) + ' MB'; }
        return Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    function uploadId() {
        var bytes = new Uint8Array(16);
        window.crypto.getRandomValues(bytes);
        return Array.prototype.map.call(bytes, function (b) { return ('0' + b.toString(16)).slice(-2); }).join('');
    }

    function showError(message) {
        errorEl.textContent = message;
        errorEl.classList.remove('d-none');
    }

    function progress(sent, total) {
        var pct = Math.floor(sent / total * 100);
        barEl.style.width = pct + '%';
        percentEl.textContent = pct + '%';
        textEl.textContent = formatSize(sent) + ' of ' + formatSize(total);
    }

    function sendChunk(file, id, offset, size, attempt) {
        var end = Math.min(file.size, offset + size);
        var form = new FormData();
        form.append('upload_id', id);
        form.append('offset', String(offset));
        form.append('total', String(file.size));
        form.append('name', file.name);
        form.append('final', end >= file.size ? '1' : '0');
        form.append('chunk', file.slice(offset, end), 'chunk');

        return fetch('/i/admin/ip-data/upload', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            body: form
        }).then(function (res) {
            if (res.status === 413) {
                if (size <= 262144) { throw new Error('The server rejected even small chunks. Raise client_max_body_size or use php artisan app:ip-data import.'); }
                return sendChunk(file, id, offset, Math.floor(size / 2), 0);
            }
            return res.json().catch(function () { return {}; }).then(function (data) {
                if (res.status === 409 && typeof data.expected_offset === 'number' && attempt < 3) {
                    return sendChunk(file, id, data.expected_offset, size, attempt + 1);
                }
                if (!res.ok) {
                    if (res.status >= 500 && attempt < 3) { return sendChunk(file, id, offset, size, attempt + 1); }
                    throw new Error(data.error || data.message || 'Upload failed.');
                }
                progress(end, file.size);
                if (data.dataset) { return data.dataset; }
                return sendChunk(file, id, end, size, 0);
            });
        });
    }

    function start(file) {
        if (busy || !file) { return; }
        errorEl.classList.add('d-none');
        var lower = file.name.toLowerCase();
        var isZip = lower.slice(-4) === '.zip';
        if (lower.slice(-4) !== '.csv' && !isZip) { showError('Choose a .csv or .csv.zip file.'); return; }
        if (isZip && !zipSupported) { showError('This server can not read zip files. Upload the unzipped .csv file instead.'); return; }

        busy = true;
        box.classList.remove('d-none');
        nameEl.textContent = file.name;
        progress(0, file.size);

        sendChunk(file, uploadId(), 0, chunkSize, 0).then(function (dataset) {
            if (dataset.status === 'failed') {
                busy = false;
                showError(dataset.error || 'The file could not be imported.');
                return;
            }
            textEl.textContent = 'Uploaded. The ' + dataset.label + ' import is starting.';
            setTimeout(function () { window.location.reload(); }, 800);
        }).catch(function (e) {
            busy = false;
            showError(e.message);
        });
    }

    input.addEventListener('change', function () { start(input.files[0]); input.value = ''; });
    ['dragenter', 'dragover'].forEach(function (name) {
        drop.addEventListener(name, function (e) { e.preventDefault(); drop.style.borderColor = '#5e72e4'; });
    });
    ['dragleave', 'drop'].forEach(function (name) {
        drop.addEventListener(name, function (e) { e.preventDefault(); drop.style.borderColor = '#c3cbe0'; });
    });
    drop.addEventListener('drop', function (e) {
        if (e.dataTransfer && e.dataTransfer.files.length) { start(e.dataTransfer.files[0]); }
    });

    function poll() {
        fetch('/i/admin/ip-data/status', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                (data.datasets || []).forEach(function (d) {
                    var el = root.querySelector('[data-dataset="' + d.id + '"]');
                    if (!el) { return; }
                    el.querySelector('[data-progress-bar]').style.width = d.progress + '%';
                    el.querySelector('[data-progress-text]').textContent = d.progress + '%';
                    el.querySelector('[data-progress-rows]').textContent = d.rows_imported.toLocaleString() + (d.rows_estimated ? ' of about ' + d.rows_estimated.toLocaleString() : '') + ' networks so far';
                });
                if (!data.running) { window.location.reload(); return; }
                setTimeout(poll, 3000);
            })
            .catch(function () { setTimeout(poll, 10000); });
    }

    if (root.getAttribute('data-running') === '1') { setTimeout(poll, 3000); }
    setTimeout(function () { var f = document.getElementById('flash'); if (f) { f.remove(); } }, 5000);
})();
</script>
@endpush
