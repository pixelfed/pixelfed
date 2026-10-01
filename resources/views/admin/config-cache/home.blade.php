@extends('admin.partial.template-full')

@section('section')
</div>
<div class="header bg-primary pb-3 mt-n4">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-12 col-lg-8">
                    <p class="display-1 text-white d-inline-block mb-0">Config Cache</p>
                </div>
                <div class="col-12 col-lg-4 d-flex flex-column flex-md-row pt-3 pt-md-0" style="gap: 10px;">
                    <div class="flex-grow-1">
                        <button type="button" id="clear-cache-btn" class="btn btn-outline-white btn-lg btn-block px-3 mb-0">
                            <i class="far fa-sync-alt mr-1"></i>
                            Reconcile &amp; Clear Cache
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid mt-5">
    <div class="row justify-content-center">
        <div class="col-12">
            <div id="cc-status" class="alert alert-success d-none"></div>
            <div id="cc-error" class="alert alert-danger d-none"></div>

            <div class="pb-3 border-bottom">
                <div class="information">
                    <ul id="cc-sync-health">
                        <p class="font-weight-bold text-muted">Sync Health</p>
                        <li><strong>Stored change-hash:</strong> <span id="cc-sync-hash">—</span></li>
                        <li><strong>Sync lock held:</strong> <span id="cc-sync-lock">—</span></li>
                    </ul>
                </div>
            </div>

            <div class="pt-4">
                <p class="font-weight-bold text-muted">
                    Managed Keys (<span id="cc-managed-count">0</span>)
                    <span class="text-muted small font-weight-normal ml-2">🔒 = secret (masked)</span>
                </p>
                <div class="table-responsive">
                    <table class="table">
                        <thead class="bg-light">
                            <tr>
                                <th scope="col" class="border-0 text-dark">Match</th>
                                <th scope="col" class="border-0 text-dark">Key</th>
                                <th scope="col" class="border-0 text-dark">Env Var</th>
                                <th scope="col" class="border-0 text-dark">List</th>
                                <th scope="col" class="border-0 text-dark">Source</th>
                                <th scope="col" class="border-0 text-dark">Locked</th>
                                <th scope="col" class="border-0 text-dark">Effective (config_cache)</th>
                                <th scope="col" class="border-0 text-dark">DB row (v)</th>
                                <th scope="col" class="border-0 text-dark">config()/env</th>
                            </tr>
                        </thead>
                        <tbody id="cc-managed-tbody"></tbody>
                    </table>
                </div>
            </div>

            <hr>

            <div class="pt-2 pb-5">
                <p class="font-weight-bold text-muted">
                    Empty Keys (<span id="cc-empty-count">0</span>)
                    <span class="text-muted small font-weight-normal ml-2">No effective value (no env, no config default, no DB row)</span>
                </p>
                <div class="table-responsive">
                    <table class="table">
                        <thead class="bg-light">
                            <tr>
                                <th scope="col" class="border-0 text-dark">Match</th>
                                <th scope="col" class="border-0 text-dark">Key</th>
                                <th scope="col" class="border-0 text-dark">Env Var</th>
                                <th scope="col" class="border-0 text-dark">List</th>
                                <th scope="col" class="border-0 text-dark">Source</th>
                                <th scope="col" class="border-0 text-dark">Locked</th>
                                <th scope="col" class="border-0 text-dark">Effective (config_cache)</th>
                                <th scope="col" class="border-0 text-dark">DB row (v)</th>
                                <th scope="col" class="border-0 text-dark">config()/env</th>
                            </tr>
                        </thead>
                        <tbody id="cc-empty-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    // Diagnostics JSON endpoints (api/v2.1); same-origin session auth via Sanctum.
    var DEBUG_URL = '/api/v2.1/admin/diagnostics/config-cache';
    var CLEAR_URL = '/api/v2.1/admin/diagnostics/config-cache/clear-cache';

    function csrf() {
        var el = document.querySelector('meta[name="csrf-token"]');
        return el ? el.content : '';
    }

    function limit(v, n) {
        v = String(v);
        return v.length > n ? v.slice(0, n) + '…' : v;
    }

    // Build a <td> with safe text (textContent avoids XSS from config values).
    function td(text, muted) {
        var cell = document.createElement('td');
        if (muted) {
            var span = document.createElement('span');
            span.className = 'text-muted';
            span.textContent = text;
            cell.appendChild(span);
        } else {
            cell.textContent = text;
        }
        return cell;
    }

    function rowEl(r) {
        var tr = document.createElement('tr');

        tr.appendChild(td(r.match ? '✓' : '✗'));

        var keyCell = document.createElement('td');
        var code = document.createElement('code');
        code.className = 'text-dark';
        code.textContent = r.key;
        keyCell.appendChild(code);
        if (r.protected) {
            var lock = document.createElement('span');
            lock.title = 'secret';
            lock.textContent = ' 🔒';
            keyCell.appendChild(lock);
        }
        tr.appendChild(keyCell);

        tr.appendChild(r.env ? td(r.env) : td('—', true));

        var listCell = document.createElement('td');
        var badge = document.createElement('span');
        badge.className = 'badge badge-primary';
        badge.textContent = r.list;
        listCell.appendChild(badge);
        tr.appendChild(listCell);

        tr.appendChild(td(r.source));
        tr.appendChild(td(r.locked ? '🔒 yes' : 'no'));
        tr.appendChild(r.effective === null ? td('null', true) : td(limit(r.effective, 120)));
        tr.appendChild(r.db === null ? td('— no row —', true) : td(limit(r.db, 120)));
        tr.appendChild(r.config === null ? td('null', true) : td(limit(r.config, 120)));

        return tr;
    }

    function emptyRow() {
        var tr = document.createElement('tr');
        var cell = document.createElement('td');
        cell.colSpan = 9;
        cell.className = 'text-center text-muted py-3';
        cell.textContent = 'No keys in this section.';
        tr.appendChild(cell);
        return tr;
    }

    function fillTable(tbody, rows) {
        tbody.innerHTML = '';
        if (!rows.length) {
            tbody.appendChild(emptyRow());
            return;
        }
        rows.forEach(function (r) { tbody.appendChild(rowEl(r)); });
    }

    function render(data) {
        var rows = data.rows || [];
        var managed = rows.filter(function (r) { return r.effective !== null && r.effective !== ''; });
        var empty = rows.filter(function (r) { return r.effective === null || r.effective === ''; });

        document.getElementById('cc-managed-count').textContent = managed.length;
        document.getElementById('cc-empty-count').textContent = empty.length;
        fillTable(document.getElementById('cc-managed-tbody'), managed);
        fillTable(document.getElementById('cc-empty-tbody'), empty);

        var sync = data.sync || {};
        document.getElementById('cc-sync-hash').textContent = sync.sync_hash || '— not set (sync has not run) —';
        var lock = sync.lock_held;
        document.getElementById('cc-sync-lock').textContent = lock === null || typeof lock === 'undefined'
            ? '❔ unknown'
            : (lock ? '⏳ held' : '✅ not held');
    }

    function showError(msg) {
        var el = document.getElementById('cc-error');
        el.textContent = msg;
        el.classList.remove('d-none');
    }

    function load() {
        fetch(DEBUG_URL, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (res) {
                if (!res.ok) { throw new Error('Failed to load diagnostics (' + res.status + ')'); }
                return res.json();
            })
            .then(render)
            .catch(function (e) { showError(e.message); });
    }

    function clearCache() {
        var btn = document.getElementById('clear-cache-btn');
        btn.disabled = true;
        document.getElementById('cc-error').classList.add('d-none');

        fetch(CLEAR_URL, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf(),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (res) {
                if (!res.ok) { throw new Error('Reconcile failed (' + res.status + ')'); }
                return res.json();
            })
            .then(function (data) {
                var status = document.getElementById('cc-status');
                status.textContent = data.message || 'Config cache reconciled and cleared.';
                status.classList.remove('d-none');
                load();
            })
            .catch(function (e) { showError(e.message); })
            .finally(function () { btn.disabled = false; });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('clear-cache-btn').addEventListener('click', clearCache);
        load();
    });
})();
</script>
@endsection
