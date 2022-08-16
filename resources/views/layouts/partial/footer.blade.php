@if(config('instance.restricted.enabled') == false)
  <footer>
    <div class="container py-5">
        <p class="text-center text-uppercase font-weight-bold small text-justify">
          <a href="{{route('site.about')}}" class="text-dark p-2">{{__('site.about')}}</a>
          <a href="{{route('site.help')}}" class="text-dark p-2">{{__('site.help')}}</a>
          <a href="{{route('site.terms')}}" class="text-dark p-2">{{__('site.terms')}}</a>
          <a href="{{route('site.privacy')}}" class="text-dark p-2">{{__('site.privacy')}}</a>
          <a href="{{route('site.language')}}" class="text-dark p-2">{{__('site.language')}}</a>
          @if(config_cache('instance.has_legal_notice'))
            <a href="/site/legal-notice" class="text-dark p-2">Legal Notice</a>
          @endif
        </p>
        <p class="text-center text-muted small mb-0">
          <span class="text-muted">© {{date('Y')}} {{config('pixelfed.domain.app')}}</span>
          <span class="mx-2">·</span>
          <a href="https://pixelfed.org" class="text-muted font-weight-bold" rel="noopener">Powered by Pixelfed</a>
          <span class="mx-2">·</span>
          <span class="text-muted">v{{config('pixelfed.version')}}</span>
        </p>
    </div>
  </footer>
  @endif
