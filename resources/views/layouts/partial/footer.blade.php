  <footer>
    <div class="container py-5">
        <p class="mb-0 text-uppercase font-weight-bold small text-justify">
          <a href="{{route('site.about')}}" class="text-primary pr-3">{{__('site.about')}}</a>
          <a href="{{route('site.help')}}" class="text-primary pr-3">{{__('site.help')}}</a>
          <a href="{{route('site.opensource')}}" class="text-primary pr-3">{{__('site.opensource')}}</a>
          <a href="{{route('site.terms')}}" class="text-primary pr-3">{{__('site.terms')}}</a>
          <a href="{{route('site.privacy')}}" class="text-primary pr-3">{{__('site.privacy')}}</a>
          <a href="{{route('site.platform')}}" class="text-primary pr-3">API</a>
          <a href="{{route('site.language')}}" class="text-primary pr-3">{{__('site.language')}}</a>
          <a href="https://pixelfed.org" class="text-muted float-right" rel="noopener" title="version {{config('pixelfed.version')}}" data-toggle="tooltip">Powered by PixelFed</a>
        </p>
    </div>
  </footer>
