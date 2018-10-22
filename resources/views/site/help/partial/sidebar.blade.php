  <div class="col-12 col-md-3 py-3" style="border-right:1px solid #ccc;">
    <ul class="nav flex-column settings-nav">
      <li class="nav-item {{request()->is('*/getting-started')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.getting-started')}}">Getting Started</a>
      </li>
      <li class="nav-item {{request()->is('*/sharing-media')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.sharing-media')}}">Sharing Photos & Videos</a>
      </li>
      <li class="nav-item {{request()->is('*/your-profile')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.your-profile')}}">Your Profile</a>
      </li>
      <li class="nav-item {{request()->is('*/stories')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.stories')}}">Stories</a>
      </li>
      <li class="nav-item {{request()->is('*/hashtags')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.hashtags')}}">Hashtags</a>
      </li>
      <li class="nav-item {{request()->is('*/discover')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.discover')}}">Discover</a>
      </li>
      <li class="nav-item {{request()->is('*/direct-messages')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.dm')}}">Direct Messages</a>
      </li>
      <li class="nav-item {{request()->is('*/timelines')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.timelines')}}">Timelines</a>
      </li>
      <li class="nav-item {{request()->is('*/embed')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.embed')}}">Embed</a>
      </li>
      <li class="nav-item">
        <hr>
      </li>
      <li class="nav-item {{request()->is('*/community-guidelines')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.community-guidelines')}}">
          Community Guidelines
        </a>
      </li>
      <li class="nav-item {{request()->is('*/what-is-the-fediverse')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.what-is-fediverse')}}">What is the fediverse?</a>
      </li>
      <li class="nav-item {{request()->is('*/controlling-visibility')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.controlling-visibility')}}">
          Controlling Visibility
        </a>
      </li>
      <li class="nav-item {{request()->is('*/abusive-activity')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.abusive-activity')}}">
          Abusive Activity
        </a>
      </li>
      <li class="nav-item {{request()->is('*/blocking-accounts')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.blocking-accounts')}}">
          Blocking Accounts
        </a>
      </li>
      <li class="nav-item {{request()->is('*/safety-tips')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.safety-tips')}}">
          Safety Tips
        </a>
      </li>
      <li class="nav-item {{request()->is('*/report-something')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.report-something')}}">
          Report Something
        </a>
      </li>
      <li class="nav-item {{request()->is('*/data-policy')?'active':''}}">
        <a class="nav-link font-weight-light text-muted" href="{{route('help.data-policy')}}">
          Data Policy
        </a>
      </li>
    </ul>
  </div>