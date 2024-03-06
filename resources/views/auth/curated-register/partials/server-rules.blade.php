@php
$rules = json_decode(config_cache('app.rules'), true)
@endphp

<div class="list-group pt-4">
    @foreach($rules as $id => $rule)
    <div class="list-group-item bg-transparent border-dark d-flex align-items-center gap-1">
        <div style="display: block;width: 40px; height:40px;">
            <div class="border border-primary text-white font-weight-bold rounded-circle d-flex justify-content-center align-items-center" style="display: block;width: 40px; height:40px;">
                {{ $id + 1 }}
            </div>
        </div>
        <div class="flex-shrink-1">
            <p class="mb-0 flex-shrink-1 flex-wrap">{{ $rule }}</p>
        </div>
    </div>
    @endforeach
</div>
