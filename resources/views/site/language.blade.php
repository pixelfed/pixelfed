@extends('layouts.app')

@section('content')
@php($current = App::getLocale())
<div class="container mt-5">  
  <div class="col-12">
    <p class="font-weight-bold text-lighter text-uppercase">{{__('site.language')}}</p>
    <div class="card border shadow-none">
      <div class="card-body row pl-md-5 ml-md-5">
        @foreach(App\Util\Localization\Localization::languages() as $lang)
        <div class="col-12 col-md-4 mb-2">
          <a href="/i/lang/{{$lang}}" class="{{$current == $lang ? 'font-weight-bold text-primary' : 'text-muted'}} pr-3 b-3">
            {{locale_get_display_language($lang, $lang)}} 
            <span class="small text-lighter">({{locale_get_display_language($lang, 'en')}})</span>
          </a>
        </div>
        @endforeach
      </div>
    </div>
  </div>
</div>
@endsection

@push('meta')
<meta property="og:description" content="Change Site Language">
@endpush
