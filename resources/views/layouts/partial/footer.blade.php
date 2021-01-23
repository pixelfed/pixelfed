@if(config('instance.restricted.enabled') == false)
  <footer>
    <div class="container py-5">
        <p class="d-flex flex-wrap justify-content-center mb-0 text-uppercase font-weight-bold small text-justify">
          <a href="{{route('site.about')}}" class="text-primary p-2">{{__('site.about')}}</a>
          @if(config('instance.contact.enabled') || config('instance.email'))
          <a href="{{route('site.contact')}}" class="text-primary p-2">{{__('site.contact-us')}}</a>
          @endif
          <a href="{{route('site.help')}}" class="text-primary p-2">{{__('site.help')}}</a>
          <a href="{{route('site.privacy')}}" class="text-primary p-2">{{__('site.privacy')}}</a>
          <a href="{{route('discover.places')}}" class="text-primary p-2">{{__('site.places')}}</a>
          <a href="{{route('site.language')}}" class="text-primary p-2">{{__('site.language')}}</a>
          <a href="https://pixelfed.org" class="text-muted p-2 ml-md-auto" rel="noopener" title="version {{config('pixelfed.version')}}" data-toggle="tooltip">Powered by Pixelfed</a>
        </p>
    </div>
  </footer>
  @endif
