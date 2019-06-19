  <div class="col-12 col-md-3 py-3 d-none d-md-block" style="border-right:1px solid #ccc;">
    <ul class="nav flex-column settings-nav">
      <li class="nav-item pl-3 {{request()->is('site/about')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('site.about')}}">{{__('site.about')}}</a>
      </li>
      
      <li class="nav-item pl-3 {{request()->is(['site/help','site/kb*'])?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('site.help')}}">{{__('site.help')}}</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('site/language')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('site.language')}}">{{__('site.language')}}</a>
      </li>
      <li class="nav-item">
        <hr>
      </li>
      <li class="nav-item pl-3 {{request()->is('site/fediverse')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('site.fediverse')}}">{{__('site.fediverse')}}</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('site/open-source')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('site.opensource')}}">{{__('site.opensource')}}</a>
      </li>
      {{-- <li class="nav-item pl-3 {{request()->is('site/banned-instances')?'active':''}}">
        <a class="nav-link lead text-muted" href="{{route('site.bannedinstances')}}">Banned Content</a>
      </li>
      <li class="nav-item pl-3">
        <a class="nav-link lead text-muted" href="#">Verification Badge</a>
      </li> --}}
      <li class="nav-item">
        <hr>
      </li>
      @if(config('instance.contact.enabled') || config('instance.email'))
      <li class="nav-item pl-3 {{request()->is('site/contact')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('site.contact')}}">{{__('site.contact-us')}}</a>
      </li>
      @endif
      <li class="nav-item pl-3 {{request()->is('site/terms')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('site.terms')}}">{{__('site.terms')}}</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('site/privacy')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('site.privacy')}}">{{__('site.privacy')}}</a>
      </li>
      {{--
        <li class="nav-item pl-3 {{request()->is('site/platform')?'active':''}}">
          <a class="nav-link lead text-muted" href="{{route('site.platform')}}">Platform</a>
        </li>
        <li class="nav-item pl-3 {{request()->is('site/libraries')?'active':''}}">
          <a class="nav-link lead text-muted" href="{{route('site.libraries')}}">Libraries</a>
        </li>
      --}}
    </ul>
  </div>