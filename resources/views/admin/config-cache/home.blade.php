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
                        <form method="POST" action="{{ route('admin.config-cache.clear') }}" class="mb-0">
                            @csrf
                            <button type="submit" class="btn btn-outline-white btn-lg btn-block px-3 mb-0">
                                <i class="far fa-sync-alt mr-1"></i>
                                Reconcile &amp; Clear Cache
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid mt-5">
    <div class="row justify-content-center">
        <div class="col-12">
            @if(session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @php
                $managedRows = array_values(array_filter($rows, fn ($r) => $r['effective'] !== null && $r['effective'] !== ''));
                $emptyRows = array_values(array_filter($rows, fn ($r) => $r['effective'] === null || $r['effective'] === ''));
            @endphp

            <div class="pb-3 border-bottom">
                <div class="information">
                    <ul>
                        <p class="font-weight-bold text-muted">
                            Sync Health
                        </p>
                        <li>
                            <strong>Stored change-hash:</strong>
                            @if($sync['sync_hash'])
                                <span><code>{{ $sync['sync_hash'] }}</code></span>
                            @else
                                <span>— not set (sync has not run) —</span>
                            @endif
                        </li>
                        <li>
                            <strong>Sync lock held:</strong>
                            <span>{{ $sync['lock_held'] === null ? '❔ unknown' : ($sync['lock_held'] ? '⏳ held' : '✅ not held') }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="pt-4">
                <p class="font-weight-bold text-muted">
                    Managed Keys ({{ count($managedRows) }})
                    <span class="text-muted small font-weight-normal ml-2">🔒 = secret (masked)</span>
                </p>
                @include('admin.config-cache._key-table', ['rows' => $managedRows])
            </div>

            <hr>

            <div class="pt-2 pb-5">
                <p class="font-weight-bold text-muted">
                    Empty Keys ({{ count($emptyRows) }})
                    <span class="text-muted small font-weight-normal ml-2">No effective value (no env, no config default, no DB row)</span>
                </p>
                @include('admin.config-cache._key-table', ['rows' => $emptyRows])
            </div>
        </div>
    </div>
</div>
@endsection
