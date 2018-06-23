@extends('site.partial.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Language</h3>
  </div>
  <hr>
  <div class="alert alert-info font-weight-bold">We’re still working on localization support!</div>
  <p class="lead">Current Locale: <span class="font-weight-bold">{{App::getLocale()}}</span></p>
  <p class="lead">Select from one of the supported languages:</p>
  <ul class="list-group">
    <a class="list-group-item font-weight-bold" href="/i/lang/en">English</a>
  </ul>
@endsection

@push('meta')
<meta property="og:description" content="Language">
@endpush
