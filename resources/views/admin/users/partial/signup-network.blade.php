@php($signupIp = $user->app_register_ip)
@php($blockNetwork = $network ? ($network['asn']['network'] ?? $network['country']['network'] ?? null) : null)
@php($blockNetwork = $blockNetwork && \App\Services\RegistrationBlockService::isValidIpOrCidr($blockNetwork) ? $blockNetwork : null)
@if($signupIp || $network)
<div class="card shadow-none border mt-2 mb-4">
   <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap" style="gap: .5rem;">
       <span class="font-weight-bold text-dark">Signup network</span>
       @if($ipDataAvailable)
       <a href="{{ route('admin.ip-data') }}" class="small text-muted">IP Data</a>
       @endif
   </div>
   <div class="card-body">
       @if($network)
       <div class="row">
           <div class="col-12 col-sm-6 col-lg-4 mb-3">
               <p class="details-list-item-title">ip address</p>
               <p class="details-list-item-value text-monospace text-truncate">{{ $signupIp ?: 'Not recorded' }}</p>
           </div>
           <div class="col-12 col-sm-6 col-lg-4 mb-3">
               <p class="details-list-item-title">country</p>
               @if($network['country'])
               <p class="details-list-item-value text-truncate">
                   <span class="badge badge-secondary mr-1">{{ $network['country']['code'] }}</span>{{ $network['country']['name'] }}
               </p>
               @else
               <p class="details-list-item-value text-muted">Unknown</p>
               @endif
           </div>
           <div class="col-12 col-sm-6 col-lg-4 mb-3">
               <p class="details-list-item-title">asn</p>
               @if($network['asn'])
               <p class="details-list-item-value text-truncate">
                   <a href="{{ route('admin.users', ['a' => 'search', 'q' => 'asn:'.$network['asn']['number']]) }}">AS{{ $network['asn']['number'] }}</a>
                   <span class="text-muted small">{{ $network['asn']['name'] }}</span>
               </p>
               @else
               <p class="details-list-item-value text-muted">Unknown</p>
               @endif
           </div>
           <div class="col-12 col-sm-6 col-lg-4 mb-3">
               <p class="details-list-item-title">organization</p>
               <p class="details-list-item-value text-truncate">{{ $network['asn']['org'] ?? 'Unknown' }}</p>
           </div>
           <div class="col-12 col-sm-6 col-lg-4 mb-3">
               <p class="details-list-item-title">domain</p>
               <p class="details-list-item-value text-truncate">{{ $network['asn']['domain'] ?? 'Unknown' }}</p>
           </div>
           <div class="col-12 col-sm-6 col-lg-4 mb-3">
               <p class="details-list-item-title">network</p>
               <p class="details-list-item-value text-monospace text-truncate">{{ $network['asn']['network'] ?? $network['country']['network'] ?? 'Unknown' }}</p>
           </div>
       </div>
       <div class="d-flex flex-wrap border-top pt-3" style="gap: .5rem;">
           @if($signupIp)
               @if($signupIpBlock)
               <span class="btn btn-sm btn-danger disabled">IP blocked by {{ $signupIpBlock['pattern'] }}</span>
               @else
               <a href="{{ route('admin.registration-blocks', ['type' => 'ip', 'status' => 'all', 'pattern' => $signupIp, 'note' => 'Signup IP of @'.$user->username]) }}#add-blocks" class="btn btn-sm btn-outline-danger font-weight-bold">
                   <i class="fas fa-ban mr-1"></i> Block IP
               </a>
               @endif
           @endif
           @if($blockNetwork)
           <a href="{{ route('admin.registration-blocks', ['type' => 'ip', 'status' => 'all', 'pattern' => $blockNetwork, 'note' => 'Network of @'.$user->username.($network['asn'] ? ', AS'.$network['asn']['number'].' '.($network['asn']['org'] ?? '') : '')]) }}#add-blocks" class="btn btn-sm btn-outline-danger font-weight-bold">
               <i class="fas fa-ban mr-1"></i> Block {{ $blockNetwork }}
           </a>
           @endif
           @if($network['asn'])
           <a href="{{ route('admin.registration-blocks', ['type' => 'asn', 'status' => 'all', 'pattern' => 'AS'.$network['asn']['number'], 'note' => 'AS'.$network['asn']['number'].' '.($network['asn']['org'] ?? '').', from @'.$user->username]) }}#add-blocks" class="btn btn-sm btn-outline-danger font-weight-bold">
               <i class="fas fa-ban mr-1"></i> Block AS{{ $network['asn']['number'] }}
           </a>
           @endif
           @if($signupIp)
           <a href="{{ route('admin.ip-data', ['ip' => $signupIp]) }}#lookup" class="btn btn-sm btn-outline-primary font-weight-bold">
               <i class="fas fa-search mr-1"></i> Open in IP lookup
           </a>
           @endif
       </div>
       @elseif(! $ipDataAvailable)
       <p class="mb-0 text-muted">Signed up from <span class="text-monospace">{{ $signupIp }}</span>. Upload IP data on the <a href="{{ route('admin.ip-data') }}">IP Data</a> page to see its country and network.</p>
       @else
       <p class="mb-0 text-muted">No match for <span class="text-monospace">{{ $signupIp }}</span> in the IP data.</p>
       @endif
   </div>
   @if($network)
   <div class="card-footer bg-white small text-muted">
       Block buttons open Registration Blocks with the pattern and a note filled in. IP address data powered by <a href="https://iplocate.io" rel="noopener">IPLocate.io</a>
   </div>
   @endif
</div>
@endif

@if($signupIp && $sameIpCount > 0)
<div class="card shadow-none border mb-4">
   <div class="card-header bg-white d-flex justify-content-between align-items-center">
       <span class="font-weight-bold text-dark">Same signup IP</span>
       <span class="small text-muted">{{ $sameIpCount }} other {{ \Illuminate\Support\Str::plural('account', $sameIpCount) }}</span>
   </div>
   <div class="list-group list-group-flush">
       @foreach($sameIp as $other)
       <a href="/i/admin/users/show/{{ $other->id }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
           <span>
               <span class="font-weight-bold">{{ $other->username }}</span>
               <span class="small text-muted ml-2">Joined {{ $other->created_at?->diffForHumans() }}</span>
           </span>
           @if($other->status && str_starts_with($other->status, 'delete'))
           <span class="badge badge-danger">Deleted</span>
           @elseif($other->status || $other->delete_after)
           <span class="badge badge-warning">{{ ucfirst($other->status ?: 'Pending delete') }}</span>
           @else
           <span class="badge badge-secondary">Active</span>
           @endif
       </a>
       @endforeach
   </div>
   @if($sameIpCount > $sameIp->count())
   <div class="card-footer bg-white">
       <a href="{{ route('admin.users', ['a' => 'search', 'q' => 'ip:'.$signupIp]) }}" class="small font-weight-bold">View all {{ $sameIpCount }}</a>
   </div>
   @endif
</div>
@endif
