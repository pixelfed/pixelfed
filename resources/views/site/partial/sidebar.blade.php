  <div class="col-12 col-md-3 py-3" style="border-right:1px solid #ccc;">
    <ul class="nav flex-column settings-nav">
      <li class="nav-item pl-3 {{request()->is('site/about')?'active':''}}">
        <a class="nav-link lead text-muted" href="{{route('site.about')}}">About</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('site/features')?'active':''}}">
        <a class="nav-link lead text-muted" href="{{route('site.features')}}">Features</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('site/help')?'active':''}}">
        <a class="nav-link lead text-muted" href="{{route('site.help')}}">Help</a>
      </li>
      <li class="nav-item">
        <hr>
      </li>
      <li class="nav-item pl-3 {{request()->is('site/fediverse')?'active':''}}">
        <a class="nav-link lead text-muted" href="{{route('site.fediverse')}}">Fediverse</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('site/open-source')?'active':''}}">
        <a class="nav-link lead text-muted" href="{{route('site.opensource')}}">Open Source</a>
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
      <li class="nav-item pl-3 {{request()->is('site/terms')?'active':''}}">
        <a class="nav-link lead text-muted" href="{{route('site.terms')}}">Terms</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('site/privacy')?'active':''}}">
        <a class="nav-link lead text-muted" href="{{route('site.privacy')}}">Privacy</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('site/platform')?'active':''}}">
        <a class="nav-link lead text-muted" href="{{route('site.platform')}}">Platform</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('site/libraries')?'active':''}}">
        <a class="nav-link lead text-muted" href="{{route('site.libraries')}}">Libraries</a>
      </li>
    </ul>
  </div>