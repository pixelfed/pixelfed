@php
$cid = 'col' . str_random(6);
@endphp
<p>
  <a class="text-dark font-weight-bold" data-toggle="collapse" href="#{{$cid}}" role="button" aria-expanded="false" aria-controls="{{$cid}}">
    <i class="fas fa-chevron-down mr-2"></i>
    {{ $title }}
  </a>
  <div class="collapse" id="{{$cid}}">
    {{ $slot }}
  </div>
</p>
