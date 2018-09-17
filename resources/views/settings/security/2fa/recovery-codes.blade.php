@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Two-Factor Authentication Recovery Codes</h3>
  </div>

  <hr>
  
  <p class="lead pb-3">
  	Each code can only be used once.
  </p>

  <p class="lead"></p>
  <ul class="list-group">
  	@foreach($codes as $code)
  	<li class="list-group-item"><code>{{$code}}</code></li>
  	@endforeach
  </ul>

@endsection