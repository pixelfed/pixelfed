  <div class="col-12 col-md-3 py-3" style="border-right:1px solid #ccc;">
    <ul class="nav flex-column settings-nav">
      <li class="nav-item pl-3 {{request()->is('dashboard')?'active':''}}">
        <a class="nav-link font-weight-bold text-muted" href="{{route('admin.home')}}">Dashboard</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('alerts*')?'active':''}}">
        <a class="nav-link font-weight-bold text-muted" href="#">Alerts</a>
      </li>
      <li class="nav-item">
        <hr>
      </li>
      <li class="nav-item pl-3 {{request()->is('instances*')?'active':''}}">
        <a class="nav-link font-weight-bold text-muted" href="#">Instances</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('media*')?'active':''}}">
        <a class="nav-link font-weight-bold text-muted" href="{{route('admin.media')}}">Media</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('reports*')?'active':''}}">
        <a class="nav-link font-weight-bold text-muted" href="{{route('admin.reports')}}">Reports</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('statuses*')?'active':''}}">
        <a class="nav-link font-weight-bold text-muted" href="{{route('admin.statuses')}}">Statuses</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('users*')?'active':''}}">
        <a class="nav-link font-weight-bold text-muted" href="{{route('admin.users')}}">Users</a>
      </li>
      <li class="nav-item">
        <hr>
      </li>
      <li class="nav-item pl-3">
        <a class="nav-link font-weight-bold text-muted" href="/horizon">Redis Queue</a>
      </li>
      <li class="nav-item pl-3 {{request()->is('settings*')?'active':''}}">
        <a class="nav-link font-weight-bold text-muted" href="#">Settings</a>
      </li>
    </ul>
  </div>