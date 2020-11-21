@section('menu')
<ul class="nav flex-column settings-nav">
  <li class="nav-item pl-3">
    <a class="nav-link text-muted {{request()->is('*settings') ? 'font-weight-bold':''}}" href="{{route('admin.settings')}}">Home</a>
  </li>
  <li class="nav-item pl-3">
    <a class="nav-link text-muted {{request()->is('*settings/backups') ? 'font-weight-bold':''}}" href="{{route('admin.settings.backups')}}">Backups</a>
  </li>
  <li class="nav-item pl-3">
    <a class="nav-link text-muted {{request()->is('*settings/config') ? 'font-weight-bold':''}}" href="{{route('admin.settings.config')}}">Configuration</a>
  </li>
  <li class="nav-item pl-3">
    <a class="nav-link text-muted {{request()->is('*settings/features') ? 'font-weight-bold':''}}" href="{{route('admin.settings.features')}}">Features</a>
  </li>
  <li class="nav-item pl-3">
    <a class="nav-link text-muted {{request()->is('*settings/maintenance') ? 'font-weight-bold':''}}" href="{{route('admin.settings.maintenance')}}">Maintenance</a>
  </li>
  <li class="nav-item pl-3">
    <a class="nav-link text-muted {{request()->is('*settings/page*') ? 'font-weight-bold':''}}" href="{{route('admin.settings.pages')}}">Pages</a>
  </li>
  <li class="nav-item pl-3">
    <a class="nav-link text-muted {{request()->is('*settings/storage') ? 'font-weight-bold':''}}" href="{{route('admin.settings.storage')}}">Storage</a>
  </li>
  <li class="nav-item pl-3">
    <a class="nav-link text-muted {{request()->is('*settings/system') ? 'font-weight-bold':''}}" href="{{route('admin.settings.system')}}">System</a>
  </li>
    </ul>
@endsection