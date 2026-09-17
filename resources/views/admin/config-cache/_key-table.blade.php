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
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['match'] ? '✓' : '✗' }}</td>
                    <td>
                        <code class="text-dark">{{ $row['key'] }}</code>
                        @if($row['protected'])
                            <span title="secret">🔒</span>
                        @endif
                    </td>
                    <td>
                        @if($row['env'])
                            <span class="small">{{ $row['env'] }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-primary">{{ $row['list'] }}</span>
                    </td>
                    <td><span class="small">{{ $row['source'] }}</span></td>
                    <td>{{ $row['locked'] ? '🔒 yes' : 'no' }}</td>
                    <td>
                        @if($row['effective'] === null)
                            <span class="text-muted">null</span>
                        @else
                            <span class="small">{{ \Illuminate\Support\Str::limit($row['effective'], 120) }}</span>
                        @endif
                    </td>
                    <td>
                        @if($row['db'] === null)
                            <span class="text-muted">— no row —</span>
                        @else
                            <span class="small">{{ \Illuminate\Support\Str::limit($row['db'], 120) }}</span>
                        @endif
                    </td>
                    <td>
                        @if($row['config'] === null)
                            <span class="text-muted">null</span>
                        @else
                            <span class="small">{{ \Illuminate\Support\Str::limit($row['config'], 120) }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-3">No keys in this section.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
