@extends('site.partial.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">{{__('site.language')}}</h3>
  </div>
  <hr>
  <div class="alert alert-info font-weight-bold">{{__('site.l10nWip')}}!</div>
  <p class="font-weight-light">{{__('site.currentLocale')}}: <span class="font-weight-bold">{{App::getLocale()}}</span></p>
  <p class="font-weight-light">{{__('site.selectLocale')}}:</p>
  <ul class="list-group">
    @foreach(App\Util\Localization\Localization::languages() as $lang)
    <a class="list-group-item font-weight-bold" href="/i/lang/{{$lang}}">{{locale_get_display_language($lang, $lang)}}</a>
    @endforeach
  </ul>
@endsection

@push('meta')
<meta property="og:description" content="Language">
@endpush
