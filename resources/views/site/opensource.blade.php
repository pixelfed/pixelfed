@extends('site.partial.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Open Source</h3>
  </div>
  <hr>
  <section>
    {{-- GLITCH MODIFIED: branding --}}
    <p class="lead">{{__('site.the_software_that_powers_this_website_is_called')}} <a href="https://github.com/pixelfed-glitch/pixelfed">Pixelfed Glitch</a> {{__('site.and_anyone_can')}} <a href="https://github.com/pixelfed-glitch/pixelfed">{{__('site.download')}}</a> {{__('site.opensource.or')}} <a href="https://github.com/pixelfed-glitch/pixelfed">{{__('site.view')}}</a> {{__('site.the_source_code_and_run_their_own_instance')}}</p>
  </section>
@endsection

@push('meta')
{{-- GLITCH MODIFIED: branding --}}
<meta property="og:description" content="{{__('site.open_source_in_pixelfed_glitch')}}">
@endpush
