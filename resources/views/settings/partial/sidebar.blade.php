  <div class="col-12 col-md-3 py-3" style="border-right:1px solid #ccc;">
    <ul class="nav flex-column settings-nav">
      <li class="nav-item pl-3 {{request()->is('settings/home')?'active':''}}">
        <a class="nav-link font-weight-light  text-muted" href="{{route('settings')}}">Profile</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('settings/password')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('settings.password')}}">Password</a>
      </li>
      {{-- <li class="nav-item pl-3 {{request()->is('settings/email')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('settings.email')}}">Email</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('settings/notifications')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('settings.notifications')}}">Notifications</a>
      </li> --}}
  
      <li class="nav-item pl-3 {{request()->is('settings/privacy*')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('settings.privacy')}}">Privacy</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('settings/security*')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('settings.security')}}">Security</a>
      </li>
      {{-- <li class="nav-item">
        <hr>
      </li>
      <li class="nav-item pl-3 {{request()->is('*import*')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('settings.import')}}">Import</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('settings/data-export')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('settings.dataexport')}}">Export</a>
      </li>
      </li>
      <li class="nav-item">
      <hr>
      </li>
      <li class="nav-item pl-3 {{request()->is('settings/applications')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="#">Applications</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('settings/developers')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="#">Developers</a>
      </li> --}}
    </ul>
  </div>