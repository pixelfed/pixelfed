@extends('layouts.app')

@section('content')
<div class="container">
  <div class="error-page py-5 my-5 text-center">
    <h3 class="font-weight-bold">{!! $title ?? config('instance.page.404.header')!!}</h3>
    <p class="lead">{!! $body ?? config('instance.page.404.body')!!}</p>
  </div>
</div>
@endsection
