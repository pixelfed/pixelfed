<nav class="navbar navbar-expand-lg navbar-light">
  <div class="container">
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#topbarNav" aria-controls="topbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="topbarNav">
      <ul class="navbar-nav">
        <li class="nav-item mx-2 {{request()->is('*admin/dashboard')?'active':''}}">
          <a class="nav-link" href="{{route('admin.home')}}">Dashboard</a>
        </li>
        <li class="nav-item mx-2 {{request()->is('*messages*')?'active':''}}">
          <a class="nav-link font-weight-lighter text-muted" href="{{route('admin.messages')}}">Messages</a>
        </li>
        <li class="nav-item mx-2 {{request()->is('*instances*')?'active':''}}">
          <a class="nav-link font-weight-lighter text-muted" href="{{route('admin.instances')}}">Instances</a>
        </li>
        <li class="nav-item mx-2 {{request()->is('*media*')?'active':''}}">
          <a class="nav-link font-weight-lighter text-muted" href="{{route('admin.media')}}">Media</a>
        </li>
        <li class="nav-item mx-2 {{request()->is('*reports*')?'active':''}}">
          <a class="nav-link font-weight-lighter text-muted" href="{{route('admin.reports')}}">Moderation</a>
        </li>
        <li class="nav-item mx-2 {{request()->is('*profiles*')?'active':''}}">
          <a class="nav-link font-weight-lighter text-muted" href="{{route('admin.profiles')}}">Profiles</a>
        </li>
        <li class="nav-item mx-2 {{request()->is('*statuses*')?'active':''}}">
          <a class="nav-link font-weight-lighter text-muted" href="{{route('admin.statuses')}}">Statuses</a>
        </li>
        <li class="nav-item mx-2 {{request()->is('*users*')?'active':''}}">
          <a class="nav-link font-weight-lighter text-muted" href="{{route('admin.users')}}">Users</a>
        </li>
        <li class="nav-item dropdown mx-3 {{request()->is(['*settings*','*discover*', '*site-news*'])?'active':''}}">
          <a class="nav-link dropdown-toggle px-4" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            More
          </a>
          <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
            <a class="dropdown-item font-weight-bold {{request()->is('*apps*')?'active':''}}" href="{{route('admin.apps')}}">Apps</a>
            <a class="dropdown-item font-weight-bold {{request()->is('*discover*')?'active':''}}" href="{{route('admin.discover')}}">Discover</a>
            <a class="dropdown-item font-weight-bold {{request()->is('*hashtags*')?'active':''}}" href="{{route('admin.hashtags')}}">Hashtags</a>
            <a class="dropdown-item font-weight-bold {{request()->is('*site-news*')?'active':''}}" href="/i/admin/site-news">Newsroom</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item font-weight-bold" href="/horizon">Horizon</a>
            {{-- <a class="dropdown-item font-weight-bold" href="#">Websockets</a> --}}
            <div class="dropdown-divider"></div>
            <a class="dropdown-item font-weight-bold {{request()->is('*settings*')?'active':''}}" href="{{route('admin.settings')}}">Settings</a>
          </div>
        </li>
      </ul>
    </div>
  </div>
</nav>

@push('styles')
<style type="text/css">
  #topbarNav .nav-item:hover {
    border-bottom: 2px solid #08d;
    margin-bottom: -7px;
  }
  #topbarNav .nav-item.active {
    border-bottom: 2px solid #08d;
    margin-bottom: -7px;
  }
  #topbarNav .nav-item.active .nav-link {
    font-weight: bold !important;
  } 
</style>
@endpush