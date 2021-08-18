@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">{{__('settings.import')}}</h3>
  </div>
  <hr>
  <section>
    <p class="lead">{!!__('settings.importDiscription',['url'=>'#'])!!}</p>
    <p class="alert alert-warning">{!!__('settings.importWarining')!!}</p>
  </section>
  <section class="mt-4">
    <p class="small text-muted font-weight-bold text-uppercase mb-3">{{__('settings.supportedServices')}}</p>
    <p class="">
      <a class="btn btn-outline-primary font-weight-bold" href="{{route('settings.import.ig')}}">{{__('settings.importInstagram')}}</a>
    </p>
    <hr>
    <p class="small text-muted font-weight-bold text-uppercase mb-3">Coming Soon</p>
    <p class="">
      <a class="btn btn-outline-secondary font-weight-bold disabled" href="#">{{__('settings.importPixelfed')}}</a>
    </p>
    <p class="">
      <a class="btn btn-outline-secondary font-weight-bold disabled" href="#">{{__('settings.importMastodon')}}</a>
    </p>
  </section>
@endsection