  <div class="col-12 col-md-3 py-3" style="border-right:1px solid #ccc;">
    <ul class="nav flex-column settings-nav">
      <li class="nav-item pl-3 {{request()->is('settings/home')?'active':''}}">
        <a class="nav-link font-weight-light  text-muted" href="{{route('settings')}}">Profile</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('settings/password')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('settings.password')}}">Password</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('settings/privacy*')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('settings.privacy')}}">Privacy</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('settings/security*')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('settings.security')}}">Security</a>
      </li>
      <li class="nav-item">
        <hr>
      </li>
      <li class="nav-item pl-3 {{request()->is('settings/data-export')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('settings.dataexport')}}">Data Export</a>
      </li>
    </ul>
  </div>