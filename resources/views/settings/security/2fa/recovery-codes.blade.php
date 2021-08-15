@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">{{__('settings.2faRecoveryCodes')}}</h3>
  </div>

  <hr>
    @if(count($codes) > 0)
      <p class="lead pb-3">
        {{__('settings.codeUsedOnce')}}
      </p>
      <ul class="list-group">
      	@foreach($codes as $code)
      	<li class="list-group-item"><code>{{$code}}</code></li>
      	@endforeach
      </ul>
    @else
    <div class="pt-5">
      <h4 class="font-weight-bold">{{__('settings.outOfCode')}}</h4>
      <p class="lead">{{__('settings.generateMoreCode')}}</p>
      <p>
        <form method="post">
          @csrf
          <button type="submit" class="btn btn-primary font-weight-bold">{{__('settings.generateRecoveryCode')}}</button>
        </form>
      </p>
    </div>
    @endif

@endsection