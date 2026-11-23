@php($ipDataNetwork = $network ?? null)
@php($ipDataIp = $ip ?? null)
@if (! $ipDataIp && ! $ipDataNetwork)
<span class="small text-muted">IP not recorded</span>
@elseif ($ipDataNetwork)
@if ($ipDataNetwork['country'])
<div class="d-flex align-items-center" style="gap: 6px;">
    <span title="{{ $ipDataNetwork['country']['name'] }}" style="font-size: 11px; font-weight: 700; background: #f6f9fc; border: 1px solid #dfe4ec; color: #32325d; padding: 0 5px; border-radius: 4px; letter-spacing: .04em;">{{ $ipDataNetwork['country']['code'] }}</span>
    <span class="font-weight-bold text-dark small text-truncate" style="max-width: 120px;">{{ $ipDataNetwork['country']['name'] }}</span>
</div>
@endif
@if ($ipDataNetwork['asn'])
<div class="small text-truncate" style="max-width: 150px;">
    <a href="{{ route('admin.ip-data', array_filter(['ip' => $ipDataIp])) }}#lookup" class="font-weight-bold">AS{{ $ipDataNetwork['asn']['number'] }}</a>
    <span class="text-muted">{{ $ipDataNetwork['asn']['org'] ?: $ipDataNetwork['asn']['name'] }}</span>
</div>
@endif
@elseif ($available ?? false)
<span class="small text-muted">No match in IP data</span>
@endif
